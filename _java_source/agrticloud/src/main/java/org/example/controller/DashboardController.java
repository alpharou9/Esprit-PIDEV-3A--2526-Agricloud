package org.example.controller;

import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.ToggleButton;
import javafx.scene.control.ToggleGroup;
import javafx.scene.layout.VBox;
import org.example.MainApp;
import org.example.dao.DatabaseConnection;
import org.example.model.Product;
import org.example.service.ExchangeRateService;
import org.example.service.OrderService;
import org.example.service.ProductService;
import org.example.service.WeatherService;

import java.util.List;

/**
 * Dashboard controller – reads aggregate data through the service layer.
 * Never calls a DAO directly.
 */
public class DashboardController {

    @FXML private Label productCountLabel;
    @FXML private Label orderCountLabel;
    @FXML private Label revenueLabel;
    @FXML private Label dbStatusLabel;
    @FXML private Label rateLabel;
    @FXML private Label weatherLabel;
    @FXML private VBox  alertsCard;

    @FXML private ToggleButton usdBtn;
    @FXML private ToggleButton tndBtn;
    @FXML private ToggleGroup  currencyGroup;

    private final ProductService      productService = new ProductService();
    private final OrderService        orderService   = new OrderService();
    private final ExchangeRateService fx             = ExchangeRateService.getInstance();
    private final WeatherService      weather        = WeatherService.getInstance();

    private static final int LOW_STOCK_THRESHOLD = 5;

    /** Last loaded revenue (USD) so we can reformat on toggle without a DB round-trip. */
    private double lastRevenueUsd = 0;

    @FXML
    public void initialize() {
        refreshStats();
    }

    public void refreshStats() {
        loadProductCount();
        loadOrderCount();
        loadRevenue();
        loadDbStatus();
        loadLowStockAlerts();
        refreshExchangeRate();
        refreshWeather();
    }

    // -------------------------------------------------------------------------
    // Currency toggle
    // -------------------------------------------------------------------------

    @FXML
    void onCurrencyToggle() {
        // Prevent deselecting both buttons
        if (currencyGroup.getSelectedToggle() == null) {
            usdBtn.setSelected(true);
        }
        updateRevenueLabel();
    }

    private boolean isTnd() {
        return tndBtn != null && tndBtn.isSelected();
    }

    private void refreshWeather() {
        if (weatherLabel == null) return;
        weatherLabel.setText("...");
        weather.refreshIfNeeded(text -> weatherLabel.setText(text));
    }

    private void refreshExchangeRate() {
        fx.refreshIfNeeded(rate -> {
            updateRevenueLabel();
            if (fx.isStale()) {
                rateLabel.setText("⚠ Offline rate (1 USD ≈ " + String.format("%.4f", rate) + " TND)");
                rateLabel.setStyle("-fx-text-fill: #FB8C00; -fx-font-size: 11px;");
            } else {
                rateLabel.setText("1 USD = " + String.format("%.4f", rate) + " TND");
                rateLabel.setStyle("-fx-text-fill: -agri-text-muted; -fx-font-size: 11px;");
            }
        });
    }

    private void updateRevenueLabel() {
        if (isTnd()) {
            revenueLabel.setText(fx.formatTnd(lastRevenueUsd));
        } else {
            revenueLabel.setText(String.format("$%.2f", lastRevenueUsd));
        }
    }

    // -------------------------------------------------------------------------
    // Private loaders
    // -------------------------------------------------------------------------

    private void loadProductCount() {
        try {
            productCountLabel.setText(String.valueOf(productService.getProductCount()));
        } catch (Exception e) {
            productCountLabel.setText("Error");
        }
    }

    private void loadOrderCount() {
        try {
            orderCountLabel.setText(String.valueOf(orderService.getActiveOrderCount()));
        } catch (Exception e) {
            orderCountLabel.setText("Error");
        }
    }

    private void loadRevenue() {
        try {
            lastRevenueUsd = orderService.getTotalRevenue();
            updateRevenueLabel();
        } catch (Exception e) {
            revenueLabel.setText("Error");
        }
    }

    private void loadDbStatus() {
        try {
            DatabaseConnection.getConnection();
            dbStatusLabel.setText("Connected");
            dbStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
        } catch (Exception e) {
            dbStatusLabel.setText("Offline");
            dbStatusLabel.setStyle("-fx-text-fill: #F44336;");
        }
    }

    private void loadLowStockAlerts() {
        alertsCard.getChildren().removeIf(n -> n instanceof Label lbl
                && lbl.getStyleClass().contains("alert-row"));
        try {
            List<Product> lowStock = productService.getLowStockProducts(LOW_STOCK_THRESHOLD);
            if (lowStock.isEmpty()) {
                Label ok = new Label("All products have sufficient stock.");
                ok.setStyle("-fx-text-fill: #4CAF50; -fx-font-size: 12px;");
                ok.getStyleClass().add("alert-row");
                alertsCard.getChildren().add(ok);
            } else {
                for (Product p : lowStock) {
                    Label row = new Label(
                            "⚠ " + p.getName() + " — only " + p.getQuantity() +
                            " " + p.getUnit() + " left");
                    row.setStyle("-fx-text-fill: #FB8C00; -fx-font-size: 12px;");
                    row.getStyleClass().add("alert-row");
                    alertsCard.getChildren().add(row);
                }
            }
        } catch (Exception e) {
            Label err = new Label("Could not load alerts.");
            err.getStyleClass().add("alert-row");
            alertsCard.getChildren().add(err);
        }
    }
}
