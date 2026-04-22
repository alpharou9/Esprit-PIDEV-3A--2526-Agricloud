package org.example.model;

import java.time.LocalDateTime;

/**
 * Product entity – belongs to the Products module (backend / farmer side).
 * Orders depend on this class but Product has no knowledge of Order.
 */
public class Product {

    private long id;
    private long userId;       // ID of the farmer who owns this product
    private Long farmId;       // optional farm reference
    private String name;
    private String description;
    private double price;
    private int quantity;
    private String unit;       // kg, g, lb, ton, piece, dozen, liter, gallon
    private String category;   // Fruits, Vegetables, Grains, Dairy, Livestock, Other
    private String image;      // file path to product image
    /** pending | approved | rejected | sold_out */
    private String status;
    private int views;
    private LocalDateTime createdAt;
    private LocalDateTime updatedAt;

    public Product() {}

    public Product(long id, long userId, Long farmId, String name, String description,
                   double price, int quantity, String unit, String category,
                   String image, String status, int views,
                   LocalDateTime createdAt, LocalDateTime updatedAt) {
        this.id = id;
        this.userId = userId;
        this.farmId = farmId;
        this.name = name;
        this.description = description;
        this.price = price;
        this.quantity = quantity;
        this.unit = unit;
        this.category = category;
        this.image = image;
        this.status = status;
        this.views = views;
        this.createdAt = createdAt;
        this.updatedAt = updatedAt;
    }

    public long getId() { return id; }
    public void setId(long id) { this.id = id; }

    public long getUserId() { return userId; }
    public void setUserId(long userId) { this.userId = userId; }

    public Long getFarmId() { return farmId; }
    public void setFarmId(Long farmId) { this.farmId = farmId; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public double getPrice() { return price; }
    public void setPrice(double price) { this.price = price; }

    public int getQuantity() { return quantity; }
    public void setQuantity(int quantity) { this.quantity = quantity; }

    public String getUnit() { return unit; }
    public void setUnit(String unit) { this.unit = unit; }

    public String getCategory() { return category; }
    public void setCategory(String category) { this.category = category; }

    public String getImage() { return image; }
    public void setImage(String image) { this.image = image; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public int getViews() { return views; }
    public void setViews(int views) { this.views = views; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }

    public LocalDateTime getUpdatedAt() { return updatedAt; }
    public void setUpdatedAt(LocalDateTime updatedAt) { this.updatedAt = updatedAt; }

    @Override
    public String toString() {
        return name + " (" + quantity + " " + unit + " @ " + price + ")";
    }
}
