package org.example.dao;

import org.example.model.Product;

import java.sql.*;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

/**
 * JDBC access layer for the products table.
 * This class has NO dependency on orders – it only knows about products.
 */
public class ProductDAO {

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    public List<Product> getAll() throws SQLException {
        List<Product> list = new ArrayList<>();
        String sql = "SELECT * FROM products ORDER BY created_at DESC";
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) list.add(map(rs));
        }
        return list;
    }

    public List<Product> getByUserId(long userId) throws SQLException {
        List<Product> list = new ArrayList<>();
        String sql = "SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    public List<Product> getByStatus(String status) throws SQLException {
        List<Product> list = new ArrayList<>();
        String sql = "SELECT * FROM products WHERE status = ? ORDER BY created_at DESC";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, status);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    public Product getById(long id) throws SQLException {
        String sql = "SELECT * FROM products WHERE id = ?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return map(rs);
            }
        }
        return null;
    }

    public int getProductCount() throws SQLException {
        String sql = "SELECT COUNT(*) FROM products";
        try (Statement st = DatabaseConnection.getConnection().createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            if (rs.next()) return rs.getInt(1);
        }
        return 0;
    }

    // -------------------------------------------------------------------------
    // Paginated reads – used by the Orders product catalog
    // -------------------------------------------------------------------------

    /**
     * Returns approved products matching an optional search string,
     * server-side paged with LIMIT / OFFSET.
     */
    public List<Product> getApprovedPage(String search, int pageSize, int offset)
            throws SQLException {
        List<Product> list = new ArrayList<>();
        String like = "%" + (search == null ? "" : search.trim()) + "%";
        String sql = """
            SELECT * FROM products
             WHERE status = 'approved'
               AND (name LIKE ? OR description LIKE ?)
             ORDER BY name
             LIMIT ? OFFSET ?
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, like);
            ps.setString(2, like);
            ps.setInt(3, pageSize);
            ps.setInt(4, offset);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    /** Total count of approved products matching the search – for pagination math. */
    public int countApproved(String search) throws SQLException {
        String like = "%" + (search == null ? "" : search.trim()) + "%";
        String sql = """
            SELECT COUNT(*) FROM products
             WHERE status = 'approved'
               AND (name LIKE ? OR description LIKE ?)
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, like);
            ps.setString(2, like);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getInt(1);
            }
        }
        return 0;
    }

    /**
     * Returns all in-stock, non-rejected products for the order catalog.
     * Includes 'approved' (orderable) and 'pending' (visible but not orderable).
     */
    public List<Product> getCatalogPage(String search, int pageSize, int offset)
            throws SQLException {
        List<Product> list = new ArrayList<>();
        String like = "%" + (search == null ? "" : search.trim()) + "%";
        String sql = """
            SELECT * FROM products
             WHERE status IN ('approved', 'pending')
               AND quantity > 0
               AND (name LIKE ? OR description LIKE ?)
             ORDER BY status DESC, name
             LIMIT ? OFFSET ?
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, like);
            ps.setString(2, like);
            ps.setInt(3, pageSize);
            ps.setInt(4, offset);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    /** Total count for the order catalog (approved + pending, in-stock). */
    public int countCatalog(String search) throws SQLException {
        String like = "%" + (search == null ? "" : search.trim()) + "%";
        String sql = """
            SELECT COUNT(*) FROM products
             WHERE status IN ('approved', 'pending')
               AND quantity > 0
               AND (name LIKE ? OR description LIKE ?)
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, like);
            ps.setString(2, like);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getInt(1);
            }
        }
        return 0;
    }

    public List<Product> getLowStockProducts(int threshold) throws SQLException {
        List<Product> list = new ArrayList<>();
        String sql = "SELECT * FROM products WHERE quantity <= ? AND status != 'sold_out'";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setInt(1, threshold);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) list.add(map(rs));
            }
        }
        return list;
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public void insert(Product p) throws SQLException {
        String sql = """
            INSERT INTO products
                (user_id, farm_id, name, description, price, quantity, unit, category, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(
                sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setLong(1, p.getUserId());
            if (p.getFarmId() != null) ps.setLong(2, p.getFarmId()); else ps.setNull(2, Types.BIGINT);
            ps.setString(3, p.getName());
            ps.setString(4, p.getDescription());
            ps.setDouble(5, p.getPrice());
            ps.setInt(6, p.getQuantity());
            ps.setString(7, p.getUnit());
            ps.setString(8, p.getCategory());
            ps.setString(9, p.getImage());
            ps.setString(10, p.getStatus());
            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) p.setId(keys.getLong(1));
            }
        }
    }

    public void update(Product p) throws SQLException {
        String sql = """
            UPDATE products
               SET name=?, description=?, price=?, quantity=?, unit=?,
                   category=?, image=?, status=?, updated_at=NOW()
             WHERE id=?
            """;
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, p.getName());
            ps.setString(2, p.getDescription());
            ps.setDouble(3, p.getPrice());
            ps.setInt(4, p.getQuantity());
            ps.setString(5, p.getUnit());
            ps.setString(6, p.getCategory());
            ps.setString(7, p.getImage());
            ps.setString(8, p.getStatus());
            ps.setLong(9, p.getId());
            ps.executeUpdate();
        }
    }

    public void delete(long id) throws SQLException {
        String sql = "DELETE FROM products WHERE id=?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setLong(1, id);
            ps.executeUpdate();
        }
    }

    /**
     * Updates only the stock quantity and recalculates status.
     * Called exclusively by ProductService – never directly from a controller.
     */
    public void updateStock(long productId, int newQuantity) throws SQLException {
        String newStatus = newQuantity <= 0 ? "sold_out" : "approved";
        String sql = "UPDATE products SET quantity=?, status=?, updated_at=NOW() WHERE id=?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setInt(1, newQuantity);
            ps.setString(2, newStatus);
            ps.setLong(3, productId);
            ps.executeUpdate();
        }
    }

    /**
     * Updates only the status column (pending → approved / rejected).
     * Called exclusively by ProductService.
     */
    public void updateStatus(long productId, String status) throws SQLException {
        String sql = "UPDATE products SET status=?, updated_at=NOW() WHERE id=?";
        try (PreparedStatement ps = DatabaseConnection.getConnection().prepareStatement(sql)) {
            ps.setString(1, status);
            ps.setLong(2, productId);
            ps.executeUpdate();
        }
    }

    // -------------------------------------------------------------------------
    // Mapping
    // -------------------------------------------------------------------------

    private Product map(ResultSet rs) throws SQLException {
        Product p = new Product();
        p.setId(rs.getLong("id"));
        p.setUserId(rs.getLong("user_id"));
        long farmId = rs.getLong("farm_id");
        p.setFarmId(rs.wasNull() ? null : farmId);
        p.setName(rs.getString("name"));
        p.setDescription(rs.getString("description"));
        p.setPrice(rs.getDouble("price"));
        p.setQuantity(rs.getInt("quantity"));
        p.setUnit(rs.getString("unit"));
        p.setCategory(rs.getString("category"));
        p.setImage(rs.getString("image"));
        p.setStatus(rs.getString("status"));
        p.setViews(columnExists(rs, "views") ? rs.getInt("views") : 0);
        Timestamp ca = rs.getTimestamp("created_at");
        p.setCreatedAt(ca != null ? ca.toLocalDateTime() : null);
        Timestamp ua = rs.getTimestamp("updated_at");
        p.setUpdatedAt(ua != null ? ua.toLocalDateTime() : null);
        return p;
    }

    private boolean columnExists(ResultSet rs, String col) {
        try {
            rs.findColumn(col);
            return true;
        } catch (SQLException e) {
            return false;
        }
    }
}
