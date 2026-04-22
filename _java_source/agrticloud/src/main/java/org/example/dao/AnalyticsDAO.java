package org.example.dao;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Raw aggregation queries for the Analytics page.
 * All methods return simple data containers (Object[] rows) or primitives.
 * No model objects needed – the controller converts directly to chart data.
 */
public class AnalyticsDAO {

    // -------------------------------------------------------------------------
    // Revenue per day (for LineChart)
    // -------------------------------------------------------------------------

    /**
     * Returns rows of [dateString, totalRevenue] for the last {@code days} days,
     * ordered oldest-first (for a left-to-right line chart).
     */
    public List<Object[]> getRevenuePerDay(int days) throws SQLException {
        String sql =
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total_price), 0) AS rev " +
            "FROM orders " +
            "WHERE status = 'delivered' " +
            "  AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) " +
            "GROUP BY DATE(created_at) " +
            "ORDER BY day ASC";
        List<Object[]> rows = new ArrayList<>();
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setInt(1, days);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    rows.add(new Object[]{ rs.getString("day"), rs.getDouble("rev") });
                }
            }
        }
        return rows;
    }

    // -------------------------------------------------------------------------
    // Best-selling products (for BarChart)
    // -------------------------------------------------------------------------

    /**
     * Returns rows of [productName, totalQty, totalRevenue] for the top
     * {@code limit} best-selling products (by quantity delivered).
     */
    public List<Object[]> getBestSellingProducts(int limit) throws SQLException {
        String sql =
            "SELECT p.name AS pname, " +
            "       COALESCE(SUM(o.quantity), 0)    AS total_qty, " +
            "       COALESCE(SUM(o.total_price), 0) AS total_rev " +
            "FROM orders o " +
            "JOIN products p ON o.product_id = p.id " +
            "WHERE o.status = 'delivered' " +
            "GROUP BY o.product_id, p.name " +
            "ORDER BY total_qty DESC " +
            "LIMIT ?";
        List<Object[]> rows = new ArrayList<>();
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setInt(1, limit);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    rows.add(new Object[]{
                        rs.getString("pname"),
                        rs.getInt("total_qty"),
                        rs.getDouble("total_rev")
                    });
                }
            }
        }
        return rows;
    }

    // -------------------------------------------------------------------------
    // Stock value by category (for PieChart)
    // -------------------------------------------------------------------------

    /**
     * Returns rows of [category, stockValue] where stockValue = SUM(price * quantity).
     */
    public List<Object[]> getStockValueByCategory() throws SQLException {
        String sql =
            "SELECT category, COALESCE(SUM(price * quantity), 0) AS stock_val " +
            "FROM products " +
            "WHERE status != 'rejected' AND quantity > 0 " +
            "GROUP BY category " +
            "ORDER BY stock_val DESC";
        List<Object[]> rows = new ArrayList<>();
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                rows.add(new Object[]{ rs.getString("category"), rs.getDouble("stock_val") });
            }
        }
        return rows;
    }

    // -------------------------------------------------------------------------
    // KPI scalars
    // -------------------------------------------------------------------------

    public int getOrdersThisMonth() throws SQLException {
        String sql =
            "SELECT COUNT(*) FROM orders " +
            "WHERE MONTH(created_at) = MONTH(CURDATE()) " +
            "  AND YEAR(created_at)  = YEAR(CURDATE())";
        return scalar(sql);
    }

    public double getRevenueThisMonth() throws SQLException {
        String sql =
            "SELECT COALESCE(SUM(total_price), 0) FROM orders " +
            "WHERE status = 'delivered' " +
            "  AND MONTH(created_at) = MONTH(CURDATE()) " +
            "  AND YEAR(created_at)  = YEAR(CURDATE())";
        return scalarDouble(sql);
    }

    public double getAvgOrderValue() throws SQLException {
        String sql = "SELECT COALESCE(AVG(total_price), 0) FROM orders WHERE status = 'delivered'";
        return scalarDouble(sql);
    }

    public double getStockTotalValue() throws SQLException {
        String sql =
            "SELECT COALESCE(SUM(price * quantity), 0) FROM products " +
            "WHERE status != 'rejected'";
        return scalarDouble(sql);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private int scalarInt(String sql) throws SQLException {
        return scalar(sql);
    }

    private int scalar(String sql) throws SQLException {
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            return rs.next() ? rs.getInt(1) : 0;
        }
    }

    private double scalarDouble(String sql) throws SQLException {
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            return rs.next() ? rs.getDouble(1) : 0.0;
        }
    }
}
