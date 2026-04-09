package org.example.service;

import javafx.application.Platform;
import javafx.beans.property.IntegerProperty;
import javafx.beans.property.SimpleIntegerProperty;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import org.example.dao.NotificationDAO;
import org.example.dao.OrderDAO;
import org.example.model.Notification;
import org.example.model.Order;
import org.example.model.Product;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;

/**
 * Thread-safe, performance-first notification service.
 *
 * Design:
 *  - Singleton so all controllers share one in-memory list.
 *  - ObservableList<Notification> + IntegerProperty → badge binds to the property
 *    (no periodic polling, no DB queries on the FX thread ever).
 *  - push()         : DB insert in background, immediate in-memory prepend.
 *  - loadRecentAsync: background DB fetch, called once at startup or on drawer open.
 *  - checkDynamicAsync: low-stock + payment-due, called at startup + every 60 s.
 *    NEVER called on user actions like "Place Order".
 */
public class NotificationService {

    // ── Singleton ─────────────────────────────────────────────────────────────
    private static final NotificationService INSTANCE = new NotificationService();
    public  static NotificationService getInstance() { return INSTANCE; }

    // ── Config ────────────────────────────────────────────────────────────────
    private static final int RECENT_LIMIT       = 50;
    private static final int LOW_STOCK_THRESHOLD = 5;
    private static final int PAYMENT_DUE_DAYS   = 7;

    // ── In-memory store (FX thread only) ──────────────────────────────────────
    private final ObservableList<Notification> notifications =
            FXCollections.observableArrayList();
    private final IntegerProperty unreadCount = new SimpleIntegerProperty(0);

    // ── Single background thread for all DB/network work ──────────────────────
    private final ScheduledExecutorService executor =
            Executors.newSingleThreadScheduledExecutor(r -> {
                Thread t = new Thread(r, "notif-worker");
                t.setDaemon(true);
                return t;
            });

    private final NotificationDAO dao = new NotificationDAO();

    private NotificationService() {}

    // =========================================================================
    // Observable accessors — bind UI directly to these
    // =========================================================================

    public ObservableList<Notification> getNotifications() { return notifications; }
    public IntegerProperty unreadCountProperty()           { return unreadCount;   }
    public int getUnreadCount()                            { return unreadCount.get(); }

    // =========================================================================
    // Startup load — call once when app starts
    // =========================================================================

    /**
     * Loads the 50 most-recent DB notifications on a background thread.
     * Safe to call from the FX thread.
     */
    public void loadRecentAsync() {
        executor.submit(() -> {
            try {
                List<Notification> list = dao.getAll(RECENT_LIMIT);
                Platform.runLater(() -> {
                    notifications.setAll(list);
                    recalcUnread();
                });
            } catch (Exception e) {
                System.err.println("[Notif] loadRecentAsync failed: " + e.getMessage());
            }
        });
    }

    // =========================================================================
    // Push a new notification — call from ANY thread after an order action
    // =========================================================================

    /**
     * Non-blocking.
     * 1. DB INSERT runs on the background thread.
     * 2. The list is updated in-memory immediately on the FX thread.
     * The caller never waits for the DB.
     */
    public void push(Notification n) {
        // Background DB insert
        executor.submit(() -> {
            try { dao.insert(n); } catch (Exception e) {
                System.err.println("[Notif] push insert failed: " + e.getMessage());
            }
        });
        // Immediate in-memory update
        Runnable update = () -> {
            notifications.add(0, n);
            if (!n.isRead()) unreadCount.set(unreadCount.get() + 1);
        };
        if (Platform.isFxApplicationThread()) update.run();
        else Platform.runLater(update);
    }

    // =========================================================================
    // Mark-read actions — immediate in-memory, background DB
    // =========================================================================

    public void markRead(long id) {
        for (Notification n : notifications) {
            if (n.getId() == id && !n.isRead()) {
                n.setRead(true);
                unreadCount.set(Math.max(0, unreadCount.get() - 1));
                break;
            }
        }
        executor.submit(() -> { try { dao.markRead(id); } catch (Exception ignored) {} });
    }

    public void markAllReadAsync() {
        notifications.forEach(n -> n.setRead(true));
        unreadCount.set(0);
        executor.submit(() -> { try { dao.markAllRead(); } catch (Exception ignored) {} });
    }

    public void clearReadAsync() {
        notifications.removeIf(Notification::isRead);
        recalcUnread();
        executor.submit(() -> { try { dao.deleteRead(); } catch (Exception ignored) {} });
    }

    // =========================================================================
    // Dynamic checks — called at startup + every 60 s via ScheduledExecutorService
    // NEVER triggered by user actions
    // =========================================================================

    /**
     * Runs low-stock and payment-due checks in the background.
     * Replaces previously added dynamic entries; never inserts to DB.
     */
    public void checkDynamicAsync() {
        executor.submit(() -> {
            List<Notification> dynamic = new ArrayList<>();

            // Low-stock check
            try {
                ProductService ps = new ProductService();
                for (Product p : ps.getLowStockProducts(LOW_STOCK_THRESHOLD)) {
                    dynamic.add(new Notification(
                            Notification.Type.LOW_STOCK,
                            "Low stock: " + p.getName(),
                            "Only " + p.getQuantity() + " " + p.getUnit() + " remaining.",
                            p.getId(), "PRODUCT"));
                }
            } catch (Exception ignored) {}

            // Payment-due check
            try {
                OrderDAO od = new OrderDAO();
                LocalDateTime cutoff = LocalDateTime.now().minusDays(PAYMENT_DUE_DAYS);
                for (Order o : od.getAll()) {
                    if ("pending".equals(o.getStatus())
                            && o.getCreatedAt() != null
                            && o.getCreatedAt().isBefore(cutoff)) {
                        dynamic.add(new Notification(
                                Notification.Type.PAYMENT_DUE,
                                "Payment due: Order #" + o.getId(),
                                "Order #" + o.getId() + " has been pending for over "
                                        + PAYMENT_DUE_DAYS + " days.",
                                o.getId(), "ORDER"));
                    }
                }
            } catch (Exception ignored) {}

            if (!dynamic.isEmpty()) {
                Platform.runLater(() -> {
                    notifications.removeIf(n -> !n.isPersisted()); // remove old dynamic
                    notifications.addAll(dynamic);
                    notifications.sort((a, b) -> {
                        LocalDateTime ta = a.getCreatedAt() != null ? a.getCreatedAt() : LocalDateTime.MIN;
                        LocalDateTime tb = b.getCreatedAt() != null ? b.getCreatedAt() : LocalDateTime.MIN;
                        return tb.compareTo(ta);
                    });
                    recalcUnread();
                });
            }
        });
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private void recalcUnread() {
        long c = notifications.stream().filter(n -> !n.isRead()).count();
        unreadCount.set((int) c);
    }

    public void shutdown() { executor.shutdownNow(); }
}
