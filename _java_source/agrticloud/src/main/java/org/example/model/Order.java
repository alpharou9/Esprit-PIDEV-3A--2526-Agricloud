package org.example.model;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

/**
 * Order header – one row in the orders table.
 * Individual line items live in OrderDetail (order_details table).
 */
public class Order {

    private static final DateTimeFormatter FMT = DateTimeFormatter.ofPattern("MMM dd, yyyy");

    private long          id;
    private long          customerId;
    private long          productId;
    private int           quantity;
    private double        unitPrice;
    private double        totalPrice;
    /** pending | confirmed | processing | shipped | delivered | cancelled */
    private String        status;
    private String        notes;
    private LocalDateTime orderDate;
    private LocalDate     deliveryDate;
    private LocalDateTime createdAt;
    private LocalDateTime updatedAt;

    // Populated via JOIN with products – display only
    private String        productName;
    private String        productUnit;
    private String        productImage;
    private String        productCategory;

    public Order() {}

    // ── Getters / Setters ──────────────────────────────────────────────────

    public long getId()                          { return id; }
    public void setId(long id)                   { this.id = id; }

    public long getCustomerId()                  { return customerId; }
    public void setCustomerId(long cid)          { this.customerId = cid; }

    public long getProductId()                   { return productId; }
    public void setProductId(long pid)           { this.productId = pid; }

    public int getQuantity()                     { return quantity; }
    public void setQuantity(int q)               { this.quantity = q; }

    public double getUnitPrice()                 { return unitPrice; }
    public void setUnitPrice(double p)           { this.unitPrice = p; }

    public double getTotalPrice()                { return totalPrice; }
    public void setTotalPrice(double tp)         { this.totalPrice = tp; }

    public String getStatus()                    { return status; }
    public void setStatus(String status)         { this.status = status; }

    public String getNotes()                     { return notes; }
    public void setNotes(String notes)           { this.notes = notes; }

    public String getProductName()               { return productName; }
    public void setProductName(String n)         { this.productName = n; }

    public String getProductUnit()               { return productUnit; }
    public void setProductUnit(String u)         { this.productUnit = u; }

    public String getProductImage()              { return productImage; }
    public void setProductImage(String img)      { this.productImage = img; }

    public String getProductCategory()           { return productCategory; }
    public void setProductCategory(String c)     { this.productCategory = c; }

    public LocalDateTime getOrderDate()          { return orderDate; }
    public void setOrderDate(LocalDateTime od)   { this.orderDate = od; }

    public LocalDate getDeliveryDate()           { return deliveryDate; }
    public void setDeliveryDate(LocalDate dd)    { this.deliveryDate = dd; }

    public LocalDateTime getCreatedAt()          { return createdAt; }
    public void setCreatedAt(LocalDateTime ca)   { this.createdAt = ca; }

    public LocalDateTime getUpdatedAt()          { return updatedAt; }
    public void setUpdatedAt(LocalDateTime ua)   { this.updatedAt = ua; }

    /** Formatted order date for display (e.g. "Mar 01, 2026"). */
    public String getFormattedDate() {
        if (orderDate != null) return orderDate.format(FMT);
        if (createdAt  != null) return createdAt.format(FMT);
        return "—";
    }

    @Override
    public String toString() {
        return "Order #" + id + " – $" + String.format("%.2f", totalPrice);
    }
}
