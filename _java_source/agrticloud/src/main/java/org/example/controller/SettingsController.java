package org.example.controller;

import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.ToggleButton;
import org.example.MainApp;
import org.example.dao.DatabaseConnection;
import org.example.model.User;
import org.example.session.UserSession;

/**
 * Settings page controller.
 *
 * Provides:
 *  - Dark/light mode toggle.
 *  - Database connection status.
 *  - Role switcher (FARMER / ADMIN) for development / demo purposes.
 *    Switching the role calls MainController.notifyRoleChanged() so that
 *    ProductController and OrderController refresh their role-specific UI.
 */
public class SettingsController {

    @FXML private ToggleButton themeToggle;
    @FXML private Label        dbStatusLabel;
    @FXML private Label        currentRoleLabel;
    @FXML private ToggleButton farmerRoleBtn;
    @FXML private ToggleButton adminRoleBtn;

    private MainController mainController;

    @FXML
    public void initialize() {
        // Sync toggle with current dark-mode state
        if (themeToggle != null) {
            themeToggle.setSelected(MainApp.getInstance().isDarkMode());
            themeToggle.setText(MainApp.getInstance().isDarkMode() ? "ON" : "OFF");
            themeToggle.selectedProperty().addListener((obs, o, n) -> {
                MainApp.getInstance().setDarkMode(n);
                themeToggle.setText(n ? "ON" : "OFF");
                // CSS dark-mode class toggle would go here via scene.getRoot()
            });
        }
        refresh();
    }

    public void setMainController(MainController mc) {
        this.mainController = mc;
    }

    /** Refresh dynamic labels (DB status, current role). */
    public void refresh() {
        // DB status
        try {
            DatabaseConnection.getConnection();
            if (dbStatusLabel != null) {
                dbStatusLabel.setText("✓ Connected");
                dbStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            }
        } catch (Exception e) {
            if (dbStatusLabel != null) {
                dbStatusLabel.setText("✗ Offline – " + e.getMessage());
                dbStatusLabel.setStyle("-fx-text-fill: #F44336;");
            }
        }

        // Current role
        UserSession session = UserSession.getInstance();
        if (currentRoleLabel != null) {
            currentRoleLabel.setText(session.getCurrentUser().getFullName() +
                                     " (" + session.getCurrentUser().getRole() + ")");
        }
        if (farmerRoleBtn != null)
            farmerRoleBtn.setSelected(session.isFarmer());
        if (adminRoleBtn != null)
            adminRoleBtn.setSelected(session.isAdmin());
    }

    // -------------------------------------------------------------------------
    // Role switching (FXML onAction)
    // -------------------------------------------------------------------------

    @FXML
    void onSwitchToFarmer() {
        UserSession.getInstance().loginAsFarmer();
        MainApp.getInstance().showToast("Switched to FARMER role.", "info");
        refresh();
        if (mainController != null) mainController.notifyRoleChanged();
    }

    @FXML
    void onSwitchToAdmin() {
        UserSession.getInstance().loginAsAdmin();
        MainApp.getInstance().showToast("Switched to ADMIN role.", "info");
        refresh();
        if (mainController != null) mainController.notifyRoleChanged();
    }
}
