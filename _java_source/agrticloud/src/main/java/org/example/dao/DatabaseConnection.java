package org.example.dao;

import java.sql.*;

/**
 * Manages a single shared JDBC connection.
 * On first connect, ensures the orders table has the product/quantity/unit_price
 * columns needed by the current schema (no separate order_details table).
 */
public class DatabaseConnection {

    private static final String URL  = "jdbc:mysql://localhost:3306/agricloud";
    private static final String USER = "root";
    private static final String PASS = "";

    private static Connection connection;

    private DatabaseConnection() {}

    public static Connection getConnection() throws SQLException {
        if (connection == null || connection.isClosed()) {
            System.out.println("[DB] Opening connection to " + URL);
            connection = DriverManager.getConnection(URL, USER, PASS);
            System.out.println("[DB] Connected.");
            ensureSchema(connection);
        }
        return connection;
    }

    // -------------------------------------------------------------------------
    // Auto-migration: adds missing columns to orders, no new tables needed
    // -------------------------------------------------------------------------

    private static void ensureSchema(Connection conn) {
        System.out.println("[DB] Checking schema...");

        // Core product columns (new schema)
        addColumnIfMissing(conn, "orders", "product_id",    "BIGINT DEFAULT NULL");
        addColumnIfMissing(conn, "orders", "quantity",      "INT NOT NULL DEFAULT 1");
        addColumnIfMissing(conn, "orders", "unit_price",    "DOUBLE NOT NULL DEFAULT 0");

        // Order-info columns
        addColumnIfMissing(conn, "orders", "notes",         "TEXT NULL DEFAULT NULL");
        addColumnIfMissing(conn, "orders", "delivery_date", "DATE NULL DEFAULT NULL");

        // Legacy shipping columns may be NOT NULL with no default – make them nullable
        makeColumnNullable(conn, "orders", "shipping_address");
        makeColumnNullable(conn, "orders", "shipping_city");
        makeColumnNullable(conn, "orders", "shipping_postal");

        // Notifications table
        createNotificationsTable(conn);

        System.out.println("[DB] Schema OK.");
    }

    private static void addColumnIfMissing(Connection conn, String table, String col, String def) {
        try {
            String sql = "SELECT COUNT(*) FROM information_schema.COLUMNS " +
                         "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
            try (PreparedStatement ps = conn.prepareStatement(sql)) {
                ps.setString(1, table);
                ps.setString(2, col);
                try (ResultSet rs = ps.executeQuery()) {
                    if (rs.next() && rs.getInt(1) > 0) return; // already exists
                }
            }
            try (Statement st = conn.createStatement()) {
                st.executeUpdate("ALTER TABLE `" + table + "` ADD COLUMN `" + col + "` " + def);
                System.out.println("[DB] Added column " + table + "." + col);
            }
        } catch (Exception e) {
            System.err.println("[DB] Could not add " + table + "." + col + ": " + e.getMessage());
        }
    }

    private static void createNotificationsTable(Connection conn) {
        String sql =
            "CREATE TABLE IF NOT EXISTS notifications (" +
            "  id           BIGINT AUTO_INCREMENT PRIMARY KEY," +
            "  type         VARCHAR(50)  NOT NULL," +
            "  title        VARCHAR(200) NOT NULL," +
            "  message      TEXT," +
            "  related_id   BIGINT       DEFAULT NULL," +
            "  related_type VARCHAR(50)  DEFAULT NULL," +
            "  is_read      TINYINT(1)   NOT NULL DEFAULT 0," +
            "  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP" +
            ")";
        try (Statement st = conn.createStatement()) {
            st.executeUpdate(sql);
        } catch (Exception e) {
            System.err.println("[DB] Could not create notifications table: " + e.getMessage());
        }
    }

    /**
     * Finds a column's current type and, if it is NOT NULL, runs MODIFY COLUMN to allow NULLs.
     * Safe to call even if the column does not exist (no-op in that case).
     */
    private static void makeColumnNullable(Connection conn, String table, String col) {
        try {
            String sql = "SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS " +
                         "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
            String colType;
            try (PreparedStatement ps = conn.prepareStatement(sql)) {
                ps.setString(1, table);
                ps.setString(2, col);
                try (ResultSet rs = ps.executeQuery()) {
                    if (!rs.next()) return;                      // column doesn't exist
                    if ("YES".equals(rs.getString("IS_NULLABLE"))) return; // already nullable
                    colType = rs.getString("COLUMN_TYPE");
                }
            }
            try (Statement st = conn.createStatement()) {
                st.executeUpdate("ALTER TABLE `" + table +
                        "` MODIFY COLUMN `" + col + "` " + colType + " NULL DEFAULT NULL");
                System.out.println("[DB] Made " + table + "." + col + " nullable");
            }
        } catch (Exception e) {
            System.err.println("[DB] Could not modify " + table + "." + col + ": " + e.getMessage());
        }
    }
}
