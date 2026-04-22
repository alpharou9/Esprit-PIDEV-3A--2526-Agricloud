package org.example.dao;

import org.example.model.Notification;

import java.sql.*;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

/**
 * JDBC access layer for the notifications table.
 * Only ORDER_STATUS notifications are persisted; LOW_STOCK and PAYMENT_DUE
 * are generated dynamically by NotificationService and never inserted here.
 */
public class NotificationDAO {

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public void insert(Notification n) throws SQLException {
        String sql = "INSERT INTO notifications (type, title, message, related_id, related_type) " +
                     "VALUES (?, ?, ?, ?, ?)";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(
                sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, n.getType().name());
            ps.setString(2, n.getTitle());
            ps.setString(3, n.getMessage());
            ps.setLong  (4, n.getRelatedId());
            ps.setString(5, n.getRelatedType());
            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) n.setId(keys.getLong(1));
            }
        }
    }

    public void markRead(long id) throws SQLException {
        exec("UPDATE notifications SET is_read = 1 WHERE id = " + id);
    }

    public void markAllRead() throws SQLException {
        exec("UPDATE notifications SET is_read = 1");
    }

    public void deleteRead() throws SQLException {
        exec("DELETE FROM notifications WHERE is_read = 1");
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    public List<Notification> getAll(int limit) throws SQLException {
        String sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?";
        List<Notification> list = new ArrayList<>();
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setInt(1, limit);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    public int countUnread() throws SQLException {
        String sql = "SELECT COUNT(*) FROM notifications WHERE is_read = 0";
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            return rs.next() ? rs.getInt(1) : 0;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private void exec(String sql) throws SQLException {
        try (Statement st = DatabaseConnection.getConnection().createStatement()) {
            st.executeUpdate(sql);
        }
    }

    private Notification map(ResultSet rs) throws SQLException {
        Notification n = new Notification();
        n.setId(rs.getLong("id"));
        try { n.setType(Notification.Type.valueOf(rs.getString("type"))); }
        catch (IllegalArgumentException e) { n.setType(Notification.Type.ORDER_STATUS); }
        n.setTitle(rs.getString("title"));
        n.setMessage(rs.getString("message"));
        n.setRelatedId(rs.getLong("related_id"));
        n.setRelatedType(rs.getString("related_type"));
        n.setRead(rs.getInt("is_read") == 1);
        Timestamp ts = rs.getTimestamp("created_at");
        if (ts != null) n.setCreatedAt(ts.toLocalDateTime());
        return n;
    }
}
