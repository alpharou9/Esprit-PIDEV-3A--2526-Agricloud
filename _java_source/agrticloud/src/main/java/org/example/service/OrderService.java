package org.example.service;

import org.example.dao.DatabaseConnection;
import org.example.dao.OrderDAO;
import org.example.model.Order;
import org.example.model.OrderDetail;
import org.example.model.Product;
import org.example.model.User;

import java.sql.Connection;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

/**
 * Business-logic layer for Orders.
 *
 * Each cart item becomes ONE row in the orders table.
 * No separate order_details table is used.
 */
public class OrderService {

    private final OrderDAO             orderDAO       = new OrderDAO();
    private final ProductService       productService = new ProductService();

    // =========================================================================
    // Create – one orders row per cart item, all in one transaction
    // =========================================================================

    /**
     * Creates one orders-row per cart item inside a transaction.
     * Returns the list of inserted rows so the caller can push
     * in-app notifications without touching the DB again.
     */
    public List<Order> createOrder(Order meta, List<OrderDetail> details, User requester)
            throws Exception {
        if (requester.getRole() != User.Role.FARMER)
            throw new SecurityException("Only farmers can place orders.");
        if (details == null || details.isEmpty())
            throw new Exception("Please add at least one product to the order.");

        // Validate all items first
        for (OrderDetail d : details) {
            Product p = productService.getProductById(d.getProductId());
            if (p == null)
                throw new Exception("Product not found (id=" + d.getProductId() + ").");
            if ("rejected".equals(p.getStatus()))
                throw new Exception("'" + p.getName() + "' has been rejected and cannot be ordered.");
            if (p.getQuantity() < d.getQuantity())
                throw new Exception("Insufficient stock for '" + p.getName() +
                        "'. Available: " + p.getQuantity() + ", requested: " + d.getQuantity() + ".");
            d.setUnitPrice(p.getPrice());
            d.setSubtotal(p.getPrice() * d.getQuantity());
        }

        Connection conn = DatabaseConnection.getConnection();
        conn.setAutoCommit(false);
        List<Order> insertedRows = new ArrayList<>();
        try {
            for (OrderDetail d : details) {
                Order row = new Order();
                row.setCustomerId(requester.getId());
                row.setProductId(d.getProductId());
                row.setQuantity(d.getQuantity());
                row.setUnitPrice(d.getUnitPrice());
                row.setTotalPrice(d.getSubtotal());
                row.setStatus("pending");
                row.setNotes(meta.getNotes());
                row.setDeliveryDate(meta.getDeliveryDate());
                orderDAO.insert(row);  // sets row.id from generated key
                productService.decrementStock(d.getProductId(), d.getQuantity());
                insertedRows.add(row);
            }
            conn.commit();
        } catch (Exception e) {
            conn.rollback();
            throw e;
        } finally {
            conn.setAutoCommit(true);
        }
        return insertedRows;
    }

    // =========================================================================
    // Update
    // =========================================================================

    /** Updates status (and optionally product/quantity if the edit drawer changes them). */
    public void updateOrder(Order order, List<OrderDetail> newDetails, User requester)
            throws Exception {
        Order existing = orderDAO.getById(order.getId());
        if (existing == null) throw new Exception("Order not found.");
        if (requester.getRole() == User.Role.FARMER &&
                existing.getCustomerId() != requester.getId())
            throw new SecurityException("Farmers can only edit their own orders.");

        boolean active = !("cancelled".equals(existing.getStatus()) ||
                           "delivered".equals(existing.getStatus()));

        Connection conn = DatabaseConnection.getConnection();
        conn.setAutoCommit(false);
        try {
            // If new items provided, take the first one as the replacement product
            if (newDetails != null && !newDetails.isEmpty()) {
                OrderDetail nd = newDetails.get(0);
                Product p = productService.getProductById(nd.getProductId());
                if (p != null) {
                    if (active && existing.getProductId() > 0)
                        productService.incrementStock(existing.getProductId(), existing.getQuantity());
                    order.setProductId(nd.getProductId());
                    order.setQuantity(nd.getQuantity());
                    order.setUnitPrice(p.getPrice());
                    order.setTotalPrice(p.getPrice() * nd.getQuantity());
                    if (active)
                        productService.decrementStock(nd.getProductId(), nd.getQuantity());
                }
            }
            orderDAO.update(order);
            conn.commit();
        } catch (Exception e) {
            conn.rollback();
            throw e;
        } finally {
            conn.setAutoCommit(true);
        }
    }

    public void updateOrderStatus(long orderId, String newStatus, User requester)
            throws Exception {
        Order order = orderDAO.getById(orderId);
        if (order == null) throw new Exception("Order not found.");

        boolean wasFinal = "cancelled".equals(order.getStatus()) ||
                           "delivered".equals(order.getStatus());
        if ("cancelled".equals(newStatus) && !wasFinal && order.getProductId() > 0)
            productService.incrementStock(order.getProductId(), order.getQuantity());

        order.setStatus(newStatus);
        orderDAO.update(order);
    }

    // =========================================================================
    // Cancel / Delete
    // =========================================================================

    public void cancelOrder(long orderId, User requester) throws Exception {
        Order order = orderDAO.getById(orderId);
        if (order == null) throw new Exception("Order not found.");
        if (requester.getRole() == User.Role.FARMER &&
                order.getCustomerId() != requester.getId())
            throw new SecurityException("Farmers can only cancel their own orders.");
        updateOrderStatus(orderId, "cancelled", requester);
    }

    public void deleteOrder(long orderId, User requester) throws Exception {
        if (requester.getRole() != User.Role.ADMIN)
            throw new SecurityException("Only admins can permanently delete orders.");
        Order order = orderDAO.getById(orderId);
        if (order == null) return;

        boolean active = !("cancelled".equals(order.getStatus()) ||
                           "delivered".equals(order.getStatus()));
        Connection conn = DatabaseConnection.getConnection();
        conn.setAutoCommit(false);
        try {
            if (active && order.getProductId() > 0)
                productService.incrementStock(order.getProductId(), order.getQuantity());
            orderDAO.delete(orderId);
            conn.commit();
        } catch (Exception e) {
            conn.rollback();
            throw e;
        } finally {
            conn.setAutoCommit(true);
        }
    }

    // =========================================================================
    // Queries
    // =========================================================================

    public List<Order> getAllOrders() throws SQLException    { return orderDAO.getAll(); }
    public List<Order> getFarmerOrders(long id) throws SQLException { return orderDAO.getByCustomerId(id); }

    public List<Order> getAllOrdersPage(int size, int offset) throws SQLException {
        return orderDAO.getPage(size, offset);
    }
    public List<Order> getFarmerOrdersPage(long id, int size, int offset) throws SQLException {
        return orderDAO.getPageByCustomer(id, size, offset);
    }

    public int countAllOrders() throws SQLException         { return orderDAO.countAll(); }
    public int countFarmerOrders(long id) throws SQLException { return orderDAO.countByCustomer(id); }

    /**
     * Returns the product details of a single order as a one-item list,
     * so the existing details TableView still works without changes.
     */
    public List<OrderDetail> getOrderDetails(long orderId) throws SQLException {
        Order o = orderDAO.getById(orderId);
        if (o == null) return new ArrayList<>();
        OrderDetail d = new OrderDetail();
        d.setOrderId(orderId);
        d.setProductId(o.getProductId());
        d.setProductName(o.getProductName() != null ? o.getProductName() : "Unknown");
        d.setProductUnit(o.getProductUnit());
        d.setProductImage(o.getProductImage());
        d.setQuantity(o.getQuantity());
        d.setUnitPrice(o.getUnitPrice());
        d.setSubtotal(o.getTotalPrice());
        return List.of(d);
    }

    public int getActiveOrderCount() throws SQLException  { return orderDAO.getActiveOrderCount(); }
    public double getTotalRevenue()  throws SQLException  { return orderDAO.getTotalRevenue(); }
}
