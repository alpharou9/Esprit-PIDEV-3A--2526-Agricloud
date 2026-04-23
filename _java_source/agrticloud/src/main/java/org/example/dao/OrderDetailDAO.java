package org.example.dao;

import org.example.model.OrderDetail;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * JDBC access layer for the order_details table.
 * Each row represents one product line inside an order.
 */
public class OrderDetailDAO {

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /** Batch-inserts all detail rows for a given order id. */
    public void insertAll(long orderId, List<OrderDetail> details) throws SQLException {
        String sql = """
            INSERT INTO order_details (order_id, product_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            for (OrderDetail d : details) {
                ps.setLong(1, orderId);
                ps.setLong(2, d.getProductId());
                ps.setInt(3, d.getQuantity());
                ps.setDouble(4, d.getUnitPrice());
                ps.setDouble(5, d.getSubtotal());
                ps.addBatch();
            }
            ps.executeBatch();
        }
    }

    /** Deletes all detail rows belonging to an order (called before re-inserting on edit). */
    public void deleteByOrderId(long orderId) throws SQLException {
        String sql = "DELETE FROM order_details WHERE order_id = ?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, orderId);
            ps.executeUpdate();
        }
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /** Returns all detail rows for one order, joined with product name/image/unit. */
    public List<OrderDetail> getByOrderId(long orderId) throws SQLException {
        List<OrderDetail> list = new ArrayList<>();
        String sql = """
            SELECT od.*,
                   p.name  AS product_name,
                   p.image AS product_image,
                   p.unit  AS product_unit
              FROM order_details od
              LEFT JOIN products p ON od.product_id = p.id
             WHERE od.order_id = ?
             ORDER BY od.id
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, orderId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    // -------------------------------------------------------------------------
    // Mapping
    // -------------------------------------------------------------------------

    private OrderDetail map(ResultSet rs) throws SQLException {
        OrderDetail d = new OrderDetail();
        d.setId(rs.getLong("id"));
        d.setOrderId(rs.getLong("order_id"));
        d.setProductId(rs.getLong("product_id"));
        d.setProductName(rs.getString("product_name"));
        d.setProductImage(rs.getString("product_image"));
        d.setProductUnit(rs.getString("product_unit"));
        d.setQuantity(rs.getInt("quantity"));
        d.setUnitPrice(rs.getDouble("unit_price"));
        d.setSubtotal(rs.getDouble("subtotal"));
        return d;
    }
}
