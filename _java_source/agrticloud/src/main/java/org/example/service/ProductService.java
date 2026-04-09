package org.example.service;

import org.example.dao.ProductDAO;
import org.example.model.Product;
import org.example.model.User;

import java.sql.SQLException;
import java.util.List;

/**
 * Business-logic layer for the Products module.
 *
 * Responsibilities:
 *  - Enforce role rules: farmers manage their own products; admins approve/reject.
 *  - Auto-set status to "pending" when a farmer adds a product.
 *  - Expose stock mutation methods used exclusively by OrderService.
 *
 * This class has ZERO dependency on orders or OrderDAO.
 */
public class ProductService {

    private final ProductDAO productDAO = new ProductDAO();

    // -------------------------------------------------------------------------
    // Farmer operations
    // -------------------------------------------------------------------------

    /**
     * A farmer adds a new product. Status is forced to "pending" until an admin
     * approves it. The product's userId is set to the requester's ID.
     */
    public void addProduct(Product product, User requester) throws Exception {
        if (requester.getRole() != User.Role.FARMER) {
            throw new SecurityException("Only farmers can add products.");
        }
        product.setUserId(requester.getId());
        product.setStatus("pending");
        if (product.getQuantity() == 0) {
            product.setStatus("sold_out");
        }
        productDAO.insert(product);
    }

    /**
     * A farmer edits one of their own products.
     * Admins may also edit any product (e.g. to correct data before approval).
     */
    public void updateProduct(Product product, User requester) throws Exception {
        if (requester.getRole() == User.Role.FARMER) {
            Product existing = productDAO.getById(product.getId());
            if (existing == null) throw new Exception("Product not found.");
            if (existing.getUserId() != requester.getId()) {
                throw new SecurityException("Farmers can only edit their own products.");
            }
            // A farmer edit resets to pending so admin can re-review changes
            product.setStatus("pending");
        }
        if (product.getQuantity() == 0) {
            product.setStatus("sold_out");
        }
        productDAO.update(product);
    }

    /**
     * A farmer or admin deletes a product.
     * Farmers may only delete their own products.
     */
    public void deleteProduct(long productId, User requester) throws Exception {
        if (requester.getRole() == User.Role.FARMER) {
            Product existing = productDAO.getById(productId);
            if (existing == null) throw new Exception("Product not found.");
            if (existing.getUserId() != requester.getId()) {
                throw new SecurityException("Farmers can only delete their own products.");
            }
        }
        productDAO.delete(productId);
    }

    // -------------------------------------------------------------------------
    // Admin operations
    // -------------------------------------------------------------------------

    /** Admin approves a pending product, making it visible to buyers. */
    public void approveProduct(long productId, User requester) throws Exception {
        if (requester.getRole() != User.Role.ADMIN) {
            throw new SecurityException("Only admins can approve products.");
        }
        Product p = productDAO.getById(productId);
        if (p == null) throw new Exception("Product not found.");
        String newStatus = p.getQuantity() == 0 ? "sold_out" : "approved";
        productDAO.updateStatus(productId, newStatus);
    }

    /** Admin rejects a pending product. */
    public void rejectProduct(long productId, User requester) throws Exception {
        if (requester.getRole() != User.Role.ADMIN) {
            throw new SecurityException("Only admins can reject products.");
        }
        productDAO.updateStatus(productId, "rejected");
    }

    // -------------------------------------------------------------------------
    // Stock management – called by OrderService only
    // -------------------------------------------------------------------------

    /**
     * Reduces product stock by the given quantity.
     * Throws if stock is insufficient.
     */
    public void decrementStock(long productId, int quantity) throws Exception {
        Product p = productDAO.getById(productId);
        if (p == null) throw new Exception("Product not found: id=" + productId);
        int newQty = p.getQuantity() - quantity;
        if (newQty < 0) {
            throw new Exception(
                "Insufficient stock for '" + p.getName() +
                "'. Available: " + p.getQuantity() + ", requested: " + quantity);
        }
        productDAO.updateStock(productId, newQty);
    }

    /**
     * Restores product stock (e.g., when an order is cancelled).
     */
    public void incrementStock(long productId, int quantity) throws Exception {
        Product p = productDAO.getById(productId);
        if (p == null) throw new Exception("Product not found: id=" + productId);
        productDAO.updateStock(productId, p.getQuantity() + quantity);
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public Product getProductById(long id) throws SQLException {
        return productDAO.getById(id);
    }

    /** All products – used by admin dashboard. */
    public List<Product> getAllProducts() throws SQLException {
        return productDAO.getAll();
    }

    /** Products belonging to a specific farmer. */
    public List<Product> getFarmerProducts(long farmerId) throws SQLException {
        return productDAO.getByUserId(farmerId);
    }

    /** Approved products only – shown in the order form dropdown. */
    public List<Product> getApprovedProducts() throws SQLException {
        return productDAO.getByStatus("approved");
    }

    public int getProductCount() throws SQLException {
        return productDAO.getProductCount();
    }

    /** Approved products paged – used by the Orders create-order catalog. */
    public List<Product> getApprovedProductsPage(String search, int pageSize, int offset)
            throws SQLException {
        return productDAO.getApprovedPage(search, pageSize, offset);
    }

    /** Total approved products matching search – for pagination math. */
    public int countApprovedProducts(String search) throws SQLException {
        return productDAO.countApproved(search);
    }

    /**
     * All in-stock, non-rejected products for the order catalog.
     * Returns both 'approved' and 'pending' products so customers always
     * see the full catalog; pending ones are shown but cannot be ordered.
     */
    public List<Product> getCatalogPage(String search, int pageSize, int offset)
            throws SQLException {
        return productDAO.getCatalogPage(search, pageSize, offset);
    }

    /** Count for pagination of the order catalog. */
    public int countCatalog(String search) throws SQLException {
        return productDAO.countCatalog(search);
    }

    public List<Product> getLowStockProducts(int threshold) throws SQLException {
        return productDAO.getLowStockProducts(threshold);
    }
}
