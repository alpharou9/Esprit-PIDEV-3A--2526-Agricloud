package org.example.model;

/**
 * Represents a platform user. Farmers manage their own products and create
 * orders. Admins approve or reject products submitted by farmers.
 */
public class User {

    public enum Role {
        FARMER, ADMIN
    }

    private long id;
    private String username;
    private String fullName;
    private Role role;

    public User() {}

    public User(long id, String username, String fullName, Role role) {
        this.id = id;
        this.username = username;
        this.fullName = fullName;
        this.role = role;
    }

    public long getId() { return id; }
    public void setId(long id) { this.id = id; }

    public String getUsername() { return username; }
    public void setUsername(String username) { this.username = username; }

    public String getFullName() { return fullName; }
    public void setFullName(String fullName) { this.fullName = fullName; }

    public Role getRole() { return role; }
    public void setRole(Role role) { this.role = role; }

    @Override
    public String toString() {
        return fullName + " (" + role + ")";
    }
}
