package org.example.model;

/**
 * One line item inside an order.
 * Maps to the order_details table (order_id FK → orders, product_id FK → products).
 */
public class OrderDetail {

    private long   id;
    private long   orderId;
    private long   productId;
    private String productName;   // denormalized via JOIN – display only
    private String productImage;  // denormalized via JOIN – display only
    private String productUnit;   // denormalized via JOIN – display only
    private int    quantity;
    private double unitPrice;
    private double subtotal;

    public OrderDetail() {}

    // ── Getters / Setters ──────────────────────────────────────────────────

    public long getId()                      { return id; }
    public void setId(long id)               { this.id = id; }

    public long getOrderId()                 { return orderId; }
    public void setOrderId(long orderId)     { this.orderId = orderId; }

    public long getProductId()               { return productId; }
    public void setProductId(long pid)       { this.productId = pid; }

    public String getProductName()           { return productName; }
    public void setProductName(String n)     { this.productName = n; }

    public String getProductImage()          { return productImage; }
    public void setProductImage(String img)  { this.productImage = img; }

    public String getProductUnit()           { return productUnit; }
    public void setProductUnit(String u)     { this.productUnit = u; }

    public int getQuantity()                 { return quantity; }
    public void setQuantity(int q)           { this.quantity = q; }

    public double getUnitPrice()             { return unitPrice; }
    public void setUnitPrice(double p)       { this.unitPrice = p; }

    public double getSubtotal()              { return subtotal; }
    public void setSubtotal(double s)        { this.subtotal = s; }
}
