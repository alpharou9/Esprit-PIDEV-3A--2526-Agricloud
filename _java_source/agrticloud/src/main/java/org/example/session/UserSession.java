package org.example.session;

import org.example.model.User;

/**
 * Singleton that holds the currently logged-in user for the lifetime of the
 * application. Defaults to a FARMER so the app runs without a login screen;
 * the Settings page lets you switch roles during development.
 */
public class UserSession {

    private static UserSession instance;
    private User currentUser;

    private UserSession() {
        // Default: farmer persona for immediate use
        currentUser = new User(1L, "farmer1", "Ali Ben Salah", User.Role.FARMER);
    }

    public static UserSession getInstance() {
        if (instance == null) {
            instance = new UserSession();
        }
        return instance;
    }

    public User getCurrentUser() {
        return currentUser;
    }

    public void setCurrentUser(User user) {
        this.currentUser = user;
    }

    /** Convenience: switch to a predefined FARMER account. */
    public void loginAsFarmer() {
        currentUser = new User(1L, "farmer1", "Ali Ben Salah", User.Role.FARMER);
    }

    /** Convenience: switch to a predefined ADMIN account. */
    public void loginAsAdmin() {
        currentUser = new User(99L, "admin", "Admin User", User.Role.ADMIN);
    }

    public boolean isFarmer() {
        return currentUser != null && currentUser.getRole() == User.Role.FARMER;
    }

    public boolean isAdmin() {
        return currentUser != null && currentUser.getRole() == User.Role.ADMIN;
    }
}
