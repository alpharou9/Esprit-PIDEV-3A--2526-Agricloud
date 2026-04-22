package org.example.controller;

import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Bounds;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ScrollPane;
import javafx.scene.layout.*;
import javafx.stage.Popup;
import org.example.MainApp;
import org.example.model.Notification;
import org.example.service.NotificationService;

import java.io.IOException;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

/**
 * Root controller – sidebar navigation, toast layer, notification bell.
 *
 * Notification design:
 *  - Badge binds to NotificationService.unreadCountProperty() — zero DB polling.
 *  - Bell click shows a lightweight Popup with the last 5 notifications.
 *  - "View all" inside the popup opens a right-side drawer (lazy-built).
 *  - Dynamic checks run at startup + every 60 s on a background scheduler.
 */
public class MainController {

    @FXML private StackPane contentArea;
    @FXML private StackPane toastLayer;
    @FXML private StackPane notifLayer;

    @FXML private Button dashBtn;
    @FXML private Button prodBtn;
    @FXML private Button ordBtn;
    @FXML private Button setBtn;
    @FXML private Button analyticsBtn;

    @FXML private StackPane bellPane;
    @FXML private Button    bellBtn;
    @FXML private Label     bellBadge;

    // Sub-controllers
    private DashboardController  dashboardController;
    private ProductController    productController;
    private OrderController      orderController;
    private SettingsController   settingsController;
    private AnalyticsController  analyticsController;

    private Node dashPage;
    private Node prodPage;
    private Node ordPage;
    private Node setPage;
    private Node analyticsPage;

    private final NotificationService notifService = NotificationService.getInstance();

    // Popover (small, auto-hide)
    private Popup  notifPopup;
    // Full drawer overlay (lazy)
    private VBox   notifDrawer;
    private StackPane drawerOverlay;

    // 60-s dynamic-check scheduler
    private final ScheduledExecutorService dynamicScheduler =
            Executors.newSingleThreadScheduledExecutor(r -> {
                Thread t = new Thread(r, "notif-dynamic");
                t.setDaemon(true);
                return t;
            });

    // =========================================================================
    // Init
    // =========================================================================

    @FXML
    public void initialize() {
        MainApp.getInstance().setToastLayer(toastLayer);

        try {
            FXMLLoader dl = new FXMLLoader(getClass().getResource("/org/example/dashboard-view.fxml"));
            dashPage = dl.load();
            dashboardController = dl.getController();

            FXMLLoader pl = new FXMLLoader(getClass().getResource("/org/example/products-view.fxml"));
            prodPage = pl.load();
            productController = pl.getController();
            productController.setMainController(this);

            FXMLLoader ol = new FXMLLoader(getClass().getResource("/org/example/orders-view.fxml"));
            ordPage = ol.load();
            orderController = ol.getController();
            orderController.setMainController(this);

            FXMLLoader sl = new FXMLLoader(getClass().getResource("/org/example/settings-view.fxml"));
            setPage = sl.load();
            settingsController = sl.getController();
            settingsController.setMainController(this);

            FXMLLoader al = new FXMLLoader(getClass().getResource("/org/example/analytics-view.fxml"));
            analyticsPage = al.load();
            analyticsController = al.getController();

        } catch (IOException e) {
            System.err.println("[Main] Failed to load FXML pages: " + e.getMessage());
            e.printStackTrace();
        }

        // ── Badge binding — no DB polling, zero overhead ─────────────────────
        bellBadge.textProperty().bind(Bindings.createStringBinding(
                () -> {
                    int c = notifService.getUnreadCount();
                    return c > 99 ? "99+" : String.valueOf(c);
                },
                notifService.unreadCountProperty()));
        bellBadge.visibleProperty().bind(
                notifService.unreadCountProperty().greaterThan(0));

        // ── Load recent notifications from DB (background, once) ─────────────
        notifService.loadRecentAsync();

        // ── Dynamic checks at startup, then every 60 s ───────────────────────
        dynamicScheduler.scheduleAtFixedRate(
                notifService::checkDynamicAsync, 2, 60, TimeUnit.SECONDS);

        switchPage(dashPage, 0);
    }

    // =========================================================================
    // Navigation
    // =========================================================================

    @FXML void onDashboard() {
        closeNotifPopup();
        dashboardController.refreshStats();
        switchPage(dashPage, 0);
    }

    @FXML void onProducts() {
        closeNotifPopup();
        productController.refreshGrid();
        switchPage(prodPage, 1);
    }

    @FXML void onOrders() {
        closeNotifPopup();
        orderController.refreshProducts();
        orderController.refreshOrders();
        switchPage(ordPage, 2);
    }

    @FXML void onSettings() {
        closeNotifPopup();
        settingsController.refresh();
        switchPage(setPage, 3);
    }

    @FXML void onAnalytics() {
        closeNotifPopup();
        analyticsController.refreshAnalytics();
        switchPage(analyticsPage, 4);
    }

    // =========================================================================
    // Cross-page helpers
    // =========================================================================

    public void notifyProductsChanged() { orderController.refreshProducts(); }

    public void notifyRoleChanged() {
        productController.onRoleChanged();
        orderController.onRoleChanged();
        dashboardController.refreshStats();
    }

    // =========================================================================
    // Bell / Notification popover
    // =========================================================================

    @FXML
    void onBell() {
        if (notifPopup != null && notifPopup.isShowing()) {
            closeNotifPopup();
        } else {
            openNotifPopup();
        }
    }

    private void openNotifPopup() {
        closeNotifPopup();

        // ── Build popover content ─────────────────────────────────────────────
        VBox box = new VBox(0);
        box.setMinWidth(320);
        box.setPrefWidth(320);
        box.setMaxWidth(320);
        box.setStyle(
            "-fx-background-color: -agri-card;" +
            "-fx-background-radius: 10;" +
            "-fx-border-color: -agri-border;" +
            "-fx-border-radius: 10;" +
            "-fx-border-width: 1;" +
            "-fx-effect: dropshadow(gaussian,rgba(0,0,0,0.28),16,0,2,4);");

        // Header
        HBox header = new HBox(8);
        header.setAlignment(Pos.CENTER_LEFT);
        header.setPadding(new Insets(10, 12, 10, 14));
        header.setStyle("-fx-background-color: -agri-green; -fx-background-radius: 10 10 0 0;");

        Label title = new Label("Notifications");
        title.setStyle("-fx-font-weight: bold; -fx-font-size: 13px; -fx-text-fill: white;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);

        Button markAll = new Button("✓ All");
        markAll.setStyle("-fx-background-color: rgba(255,255,255,0.2); -fx-text-fill: white; -fx-font-size: 10px; -fx-padding: 3 8; -fx-background-radius: 4; -fx-cursor: hand;");
        markAll.setOnAction(e -> { notifService.markAllReadAsync(); closeNotifPopup(); });

        Button viewAll = new Button("View all");
        viewAll.setStyle("-fx-background-color: rgba(255,255,255,0.2); -fx-text-fill: white; -fx-font-size: 10px; -fx-padding: 3 8; -fx-background-radius: 4; -fx-cursor: hand;");
        viewAll.setOnAction(e -> { closeNotifPopup(); openNotifDrawer(); });

        header.getChildren().addAll(title, spacer, markAll, viewAll);

        // Recent 5 notifications
        List<Notification> recent = notifService.getNotifications();
        int limit = Math.min(5, recent.size());
        VBox listBox = new VBox(0);
        if (limit == 0) {
            Label empty = new Label("No notifications yet.");
            empty.setStyle("-fx-text-fill: -agri-text-muted; -fx-font-size: 12px; -fx-padding: 16 14;");
            listBox.getChildren().add(empty);
        } else {
            for (int i = 0; i < limit; i++) {
                listBox.getChildren().add(buildNotifRow(recent.get(i), true));
            }
            if (recent.size() > 5) {
                Label more = new Label("+" + (recent.size() - 5) + " more — click View all");
                more.setStyle("-fx-text-fill: -agri-text-muted; -fx-font-size: 11px; -fx-padding: 6 14; -fx-cursor: hand;");
                more.setOnMouseClicked(e -> { closeNotifPopup(); openNotifDrawer(); });
                listBox.getChildren().add(more);
            }
        }

        box.getChildren().addAll(header, listBox);

        // ── Show Popup anchored to the right of the bell button ───────────────
        notifPopup = new Popup();
        notifPopup.setAutoHide(true);
        notifPopup.setConsumeAutoHidingEvents(false);
        notifPopup.getContent().add(box);

        Bounds b = bellBtn.localToScreen(bellBtn.getBoundsInLocal());
        if (b != null) {
            notifPopup.show(bellBtn.getScene().getWindow(),
                    b.getMaxX() + 10,
                    b.getMinY());
        }
    }

    private void closeNotifPopup() {
        if (notifPopup != null) {
            notifPopup.hide();
            notifPopup = null;
        }
    }

    // =========================================================================
    // Full notification drawer (lazy — only built when "View all" is clicked)
    // =========================================================================

    private void openNotifDrawer() {
        if (drawerOverlay != null) return; // already open

        drawerOverlay = new StackPane();
        drawerOverlay.setStyle("-fx-background-color: rgba(0,0,0,0.35);");
        drawerOverlay.setAlignment(Pos.CENTER_RIGHT);
        drawerOverlay.setOnMouseClicked(e -> {
            if (e.getTarget() == drawerOverlay) closeNotifDrawer();
        });

        notifDrawer = new VBox(0);
        notifDrawer.setMaxWidth(360);
        notifDrawer.setPrefWidth(360);
        notifDrawer.setMaxHeight(Double.MAX_VALUE);
        notifDrawer.setStyle(
            "-fx-background-color: -agri-card;" +
            "-fx-border-color: -agri-border;" +
            "-fx-border-width: 0 0 0 1;");

        // Header
        HBox dHeader = new HBox(8);
        dHeader.setAlignment(Pos.CENTER_LEFT);
        dHeader.setPadding(new Insets(14, 14, 14, 16));
        dHeader.setStyle("-fx-background-color: -agri-green;");

        Label dTitle = new Label("All Notifications");
        dTitle.setStyle("-fx-font-weight: bold; -fx-font-size: 14px; -fx-text-fill: white;");
        Region dSpacer = new Region();
        HBox.setHgrow(dSpacer, Priority.ALWAYS);

        Button dMarkAll = new Button("✓ All");
        dMarkAll.setStyle("-fx-background-color: rgba(255,255,255,0.2); -fx-text-fill: white; -fx-font-size: 10px; -fx-padding: 3 8; -fx-background-radius: 4; -fx-cursor: hand;");
        dMarkAll.setOnAction(e -> { notifService.markAllReadAsync(); rebuildDrawerList(); });

        Button dClear = new Button("Clear read");
        dClear.setStyle("-fx-background-color: rgba(255,255,255,0.2); -fx-text-fill: white; -fx-font-size: 10px; -fx-padding: 3 8; -fx-background-radius: 4; -fx-cursor: hand;");
        dClear.setOnAction(e -> { notifService.clearReadAsync(); rebuildDrawerList(); });

        Button dClose = new Button("✕");
        dClose.setStyle("-fx-background-color: transparent; -fx-text-fill: white; -fx-font-size: 13px; -fx-padding: 2 6;");
        dClose.setOnAction(e -> closeNotifDrawer());

        dHeader.getChildren().addAll(dTitle, dSpacer, dMarkAll, dClear, dClose);

        // List (built once, content filled by rebuildDrawerList)
        VBox listBox = new VBox(0);
        listBox.setId("drawerListBox");

        ScrollPane scroll = new ScrollPane(listBox);
        scroll.setFitToWidth(true);
        scroll.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        scroll.setStyle("-fx-background: transparent; -fx-background-color: transparent;");
        VBox.setVgrow(scroll, Priority.ALWAYS);

        notifDrawer.getChildren().addAll(dHeader, scroll);
        drawerOverlay.getChildren().add(notifDrawer);
        notifLayer.getChildren().add(drawerOverlay);

        rebuildDrawerList();
    }

    private void rebuildDrawerList() {
        if (notifDrawer == null) return;
        // Find the listBox by id
        notifDrawer.getChildren().stream()
                .filter(n -> n instanceof ScrollPane)
                .map(n -> (ScrollPane) n)
                .findFirst()
                .ifPresent(scroll -> {
                    VBox listBox = (VBox) scroll.getContent();
                    listBox.getChildren().clear();
                    List<Notification> all = notifService.getNotifications();
                    if (all.isEmpty()) {
                        Label empty = new Label("No notifications yet.");
                        empty.setStyle("-fx-text-fill: -agri-text-muted; -fx-font-size: 12px; -fx-padding: 20 16;");
                        listBox.getChildren().add(empty);
                    } else {
                        for (Notification n : all) {
                            listBox.getChildren().add(buildNotifRow(n, false));
                        }
                    }
                });
    }

    private void closeNotifDrawer() {
        if (drawerOverlay != null) {
            notifLayer.getChildren().remove(drawerOverlay);
            drawerOverlay = null;
            notifDrawer   = null;
        }
    }

    // =========================================================================
    // Shared notification row builder
    // =========================================================================

    private Node buildNotifRow(Notification n, boolean compact) {
        HBox row = new HBox(10);
        row.setAlignment(Pos.CENTER_LEFT);
        row.setPadding(new Insets(compact ? 8 : 10, 12, compact ? 8 : 10, 14));
        row.setStyle(
            "-fx-background-color: " + (n.isRead() ? "transparent" : "-agri-green-bg") + ";" +
            "-fx-border-color: transparent transparent -agri-border transparent;" +
            "-fx-border-width: 0 0 1 0;");

        Label icon = new Label(n.getIcon());
        icon.setStyle("-fx-font-size: 16px; -fx-min-width: 22;");

        VBox text = new VBox(2);
        HBox.setHgrow(text, Priority.ALWAYS);

        Label titleLbl = new Label(n.getTitle());
        titleLbl.setStyle("-fx-font-weight: bold; -fx-font-size: 12px; -fx-text-fill: -agri-text;");
        titleLbl.setWrapText(true);
        titleLbl.setMaxWidth(compact ? 200 : 250);

        if (!compact && n.getMessage() != null && !n.getMessage().isBlank()) {
            String msg = n.getMessage().length() > 80
                    ? n.getMessage().substring(0, 77) + "…" : n.getMessage();
            Label msgLbl = new Label(msg);
            msgLbl.setStyle("-fx-font-size: 11px; -fx-text-fill: -agri-text-muted;");
            msgLbl.setWrapText(true);
            msgLbl.setMaxWidth(250);
            text.getChildren().addAll(titleLbl, msgLbl);
        } else {
            text.getChildren().add(titleLbl);
        }

        Label timeLbl = new Label(n.getTimeAgo());
        timeLbl.setStyle("-fx-font-size: 10px; -fx-text-fill: -agri-text-muted;");
        text.getChildren().add(timeLbl);

        row.getChildren().addAll(icon, text);

        // Read button for unread persisted notifications
        if (!n.isRead() && n.isPersisted()) {
            Button readBtn = new Button("✓");
            readBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: -agri-green; -fx-font-size: 12px; -fx-padding: 0 4; -fx-cursor: hand;");
            readBtn.setOnAction(e -> {
                notifService.markRead(n.getId());
                if (notifDrawer != null) rebuildDrawerList();
            });
            row.getChildren().add(readBtn);
        }

        // Click to navigate to related page
        if (n.getRelatedType() != null) {
            row.setStyle(row.getStyle() + "-fx-cursor: hand;");
            row.setOnMouseClicked(e -> {
                closeNotifPopup();
                closeNotifDrawer();
                if ("ORDER".equals(n.getRelatedType())) onOrders();
                else if ("PRODUCT".equals(n.getRelatedType())) onProducts();
            });
        }

        return row;
    }

    // =========================================================================
    // Internals
    // =========================================================================

    private void switchPage(Node page, int activeIndex) {
        contentArea.getChildren().setAll(page);
        Button[] btns = {dashBtn, prodBtn, ordBtn, setBtn, analyticsBtn};
        for (int i = 0; i < btns.length; i++) {
            if (btns[i] == null) continue;
            btns[i].getStyleClass().removeAll("menu-item-active");
            if (i == activeIndex) btns[i].getStyleClass().add("menu-item-active");
        }
    }
}
