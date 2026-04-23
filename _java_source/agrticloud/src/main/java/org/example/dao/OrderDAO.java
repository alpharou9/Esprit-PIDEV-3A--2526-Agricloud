package org.example.dao;

import org.example.model.Order;

import java.sql.*;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

/**
 * JDBC access layer for the orders table.
 * Each row in orders represents one product line (one product per order row).
 * Product details are fetched via LEFT JOIN with the products table.
 */
public class OrderDAO {

    // Base SELECT shared by all read queries
    private static final String SELECT =
        "SELECT o.*, " +
        "       p.name     AS product_name, " +
        "       p.unit     AS product_unit, " +
        "       p.image    AS product_image, " +
        "       p.category AS product_category " +
        "FROM orders o " +
        "LEFT JOIN products p ON o.product_id = p.id ";

    // -------------------------------------------------------------------------
    // Paginated reads
    // -------------------------------------------------------------------------

    public List<Order> getPage(int pageSize, int offset) throws SQLException {
        String sql = SELECT + "ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        return query(sql, pageSize, offset);
    }

    public List<Order> getPageByCustomer(long customerId, int pageSize, int offset) throws SQLException {
        String sql = SELECT + "WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        return query(sql, customerId, pageSize, offset);
    }

    // -------------------------------------------------------------------------
    // Count helpers
    // -------------------------------------------------------------------------

    public int countAll() throws SQLException {
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery("SELECT COUNT(*) FROM orders")) {
            return rs.next() ? rs.getInt(1) : 0;
        }
    }

    public int countByCustomer(long customerId) throws SQLException {
        String sql = "SELECT COUNT(*) FROM orders WHERE customer_id = ?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, customerId);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? rs.getInt(1) : 0;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Full list (non-paginated – stats / export)
    // -------------------------------------------------------------------------

    public List<Order> getAll() throws SQLException {
        return query(SELECT + "ORDER BY o.created_at DESC");
    }

    public List<Order> getByCustomerId(long customerId) throws SQLException {
        return query(SELECT + "WHERE o.customer_id = ? ORDER BY o.created_at DESC", customerId);
    }

    public Order getById(long id) throws SQLException {
        String sql = SELECT + "WHERE o.id = ?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? map(rs) : null;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Dashboard stats
    // -------------------------------------------------------------------------

    public int getActiveOrderCount() throws SQLException {
        String sql = "SELECT COUNT(*) FROM orders WHERE status NOT IN ('delivered','cancelled')";
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            return rs.next() ? rs.getInt(1) : 0;
        }
    }

    public double getTotalRevenue() throws SQLException {
        String sql = "SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='delivered'";
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            return rs.next() ? rs.getDouble(1) : 0.0;
        }
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public void insert(Order o) throws SQLException {
        String sql =
            "INSERT INTO orders " +
            "(customer_id, product_id, quantity, unit_price, total_price, status, notes, delivery_date) " +
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(
                sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setLong(1, o.getCustomerId());
            if (o.getProductId() > 0) ps.setLong(2, o.getProductId());
            else                      ps.setNull(2, Types.BIGINT);
            ps.setInt(3, o.getQuantity());
            ps.setDouble(4, o.getUnitPrice());
            ps.setDouble(5, o.getTotalPrice());
            ps.setString(6, o.getStatus());
            ps.setString(7, o.getNotes());  // nullable
            if (o.getDeliveryDate() != null) ps.setDate(8, Date.valueOf(o.getDeliveryDate()));
            else                             ps.setNull(8, Types.DATE);
            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) o.setId(keys.getLong(1));
            }
        }
    }

    public void update(Order o) throws SQLException {
        String sql =
            "UPDATE orders " +
            "SET total_price=?, status=?, product_id=?, quantity=?, unit_price=?, updated_at=NOW() " +
            "WHERE id=?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setDouble(1, o.getTotalPrice());
            ps.setString(2, o.getStatus());
            if (o.getProductId() > 0) ps.setLong(3, o.getProductId());
            else                      ps.setNull(3, Types.BIGINT);
            ps.setInt(4, o.getQuantity());
            ps.setDouble(5, o.getUnitPrice());
            ps.setLong(6, o.getId());
            ps.executeUpdate();
        }
    }

    public void delete(long id) throws SQLException {
        String sql = "DELETE FROM orders WHERE id=?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, id);
            ps.executeUpdate();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private List<Order> query(String sql, Object... params) throws SQLException {
        List<Order> list = new ArrayList<>();
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            for (int i = 0; i < params.length; i++) {
                if (params[i] instanceof Long)    ps.setLong(i + 1, (Long) params[i]);
                else if (params[i] instanceof Integer) ps.setInt(i + 1, (Integer) params[i]);
                else ps.setObject(i + 1, params[i]);
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    private Order map(ResultSet rs) throws SQLException {
        Order o = new Order();
        o.setId(rs.getLong("id"));
        o.setCustomerId(rs.getLong("customer_id"));
        o.setTotalPrice(rs.getDouble("total_price"));
        o.setStatus(rs.getString("status"));

        // Product columns (may be NULL if product was deleted)
        long pid = rs.getLong("product_id");
        if (!rs.wasNull()) o.setProductId(pid);

        o.setQuantity(rs.getInt("quantity"));
        o.setUnitPrice(rs.getDouble("unit_price"));
        o.setNotes(rs.getString("notes"));

        // Joined product info
        o.setProductName(rs.getString("product_name"));
        o.setProductUnit(rs.getString("product_unit"));
        o.setProductImage(rs.getString("product_image"));
        o.setProductCategory(rs.getString("product_category"));

        // Dates
        Timestamp od = rs.getTimestamp("order_date");
        if (od != null) o.setOrderDate(od.toLocalDateTime());
        Date dd = rs.getDate("delivery_date");
        if (dd != null) o.setDeliveryDate(dd.toLocalDate());
        Timestamp ca = rs.getTimestamp("created_at");
        if (ca != null) o.setCreatedAt(ca.toLocalDateTime());
        Timestamp ua = rs.getTimestamp("updated_at");
        if (ua != null) o.setUpdatedAt(ua.toLocalDateTime());
        return o;
    }
}
