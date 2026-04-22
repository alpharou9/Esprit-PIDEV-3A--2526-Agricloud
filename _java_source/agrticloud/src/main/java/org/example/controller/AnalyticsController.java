package org.example.controller;

import javafx.application.Platform;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.scene.chart.*;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import org.example.service.AnalyticsService;
import org.example.service.ExchangeRateService;

import java.util.List;

/**
 * Controller for the Analytics page.
 * All DB queries run on a background thread (Task) to keep the UI responsive.
 */
public class AnalyticsController {

    // ── KPI labels ────────────────────────────────────────────────────────────
    @FXML private Label kpiOrdersMonth;
    @FXML private Label kpiRevenueMonth;
    @FXML private Label kpiAvgOrder;
    @FXML private Label kpiStockValue;

    // ── Charts ────────────────────────────────────────────────────────────────
    @FXML private LineChart<String, Number> revenueChart;
    @FXML private BarChart<String, Number>  bestSellerChart;
    @FXML private PieChart                  stockPieChart;

    // ── Top-5 table ───────────────────────────────────────────────────────────
    @FXML private TableView<Object[]> top5Table;

    // ── Spinners ─────────────────────────────────────────────────────────────
    @FXML private ProgressIndicator revenueSpinner;
    @FXML private ProgressIndicator sellerSpinner;
    @FXML private ProgressIndicator pieSpinner;

    // ── Period buttons ────────────────────────────────────────────────────────
    @FXML private Button btn7d;
    @FXML private Button btn14d;
    @FXML private Button btn30d;

    private int selectedDays = 7;

    private final AnalyticsService  analyticsService = new AnalyticsService();
    private final ExchangeRateService fx             = ExchangeRateService.getInstance();

    // =========================================================================
    // Init
    // =========================================================================

    @FXML
    public void initialize() {
        setupTop5Table();
    }

    /** Called by MainController each time the Analytics page is shown. */
    public void refreshAnalytics() {
        loadKpis();
        loadRevenueChart(selectedDays);
        loadBestSellers();
        loadStockPie();
    }

    // =========================================================================
    // Period buttons
    // =========================================================================

    @FXML void onPeriod7()  { setPeriod(7,  btn7d);  }
    @FXML void onPeriod14() { setPeriod(14, btn14d); }
    @FXML void onPeriod30() { setPeriod(30, btn30d); }

    private void setPeriod(int days, Button active) {
        selectedDays = days;
        for (Button b : new Button[]{btn7d, btn14d, btn30d}) {
            b.getStyleClass().remove("period-btn-active");
        }
        active.getStyleClass().add("period-btn-active");
        loadRevenueChart(days);
    }

    // =========================================================================
    // Async loaders
    // =========================================================================

    private void loadKpis() {
        kpiOrdersMonth.setText("...");
        kpiRevenueMonth.setText("...");
        kpiAvgOrder.setText("...");
        kpiStockValue.setText("...");

        Task<double[]> task = new Task<>() {
            @Override
            protected double[] call() throws Exception {
                return new double[]{
                    analyticsService.getOrdersThisMonth(),
                    analyticsService.getRevenueThisMonth(),
                    analyticsService.getAvgOrderValue(),
                    analyticsService.getStockTotalValue()
                };
            }
        };
        task.setOnSucceeded(e -> {
            double[] v = task.getValue();
            kpiOrdersMonth .setText(String.valueOf((int) v[0]));
            kpiRevenueMonth.setText(String.format("$%.2f", v[1]));
            kpiAvgOrder    .setText(String.format("$%.2f", v[2]));
            kpiStockValue  .setText(String.format("$%.0f", v[3]));
        });
        task.setOnFailed(e -> {
            kpiOrdersMonth.setText("—");
            kpiRevenueMonth.setText("—");
            kpiAvgOrder.setText("—");
            kpiStockValue.setText("—");
        });
        daemon(task);
    }

    private void loadRevenueChart(int days) {
        revenueSpinner.setVisible(true);
        revenueChart.getData().clear();

        Task<List<Object[]>> task = new Task<>() {
            @Override
            protected List<Object[]> call() throws Exception {
                return analyticsService.getRevenuePerDay(days);
            }
        };
        task.setOnSucceeded(e -> {
            XYChart.Series<String, Number> series = new XYChart.Series<>();
            series.setName("Revenue (USD)");
            for (Object[] row : task.getValue()) {
                series.getData().add(new XYChart.Data<>(
                        String.valueOf(row[0]),
                        (Number) row[1]));
            }
            revenueChart.getData().add(series);
            revenueSpinner.setVisible(false);
        });
        task.setOnFailed(e -> revenueSpinner.setVisible(false));
        daemon(task);
    }

    private void loadBestSellers() {
        sellerSpinner.setVisible(true);
        bestSellerChart.getData().clear();

        Task<List<Object[]>> task = new Task<>() {
            @Override
            protected List<Object[]> call() throws Exception {
                return analyticsService.getBestSellers(8);
            }
        };
        task.setOnSucceeded(e -> {
            XYChart.Series<String, Number> series = new XYChart.Series<>();
            series.setName("Units Sold");
            for (Object[] row : task.getValue()) {
                String name = (String) row[0];
                if (name != null && name.length() > 14) name = name.substring(0, 12) + "…";
                series.getData().add(new XYChart.Data<>(name, (Number) row[1]));
            }
            bestSellerChart.getData().add(series);
            // Also refresh top-5 table (same data)
            refreshTop5(task.getValue());
            sellerSpinner.setVisible(false);
        });
        task.setOnFailed(e -> sellerSpinner.setVisible(false));
        daemon(task);
    }

    private void loadStockPie() {
        pieSpinner.setVisible(true);
        stockPieChart.getData().clear();

        Task<List<Object[]>> task = new Task<>() {
            @Override
            protected List<Object[]> call() throws Exception {
                return analyticsService.getStockByCategory();
            }
        };
        task.setOnSucceeded(e -> {
            for (Object[] row : task.getValue()) {
                String cat = row[0] != null ? (String) row[0] : "Other";
                double val = (double) row[1];
                stockPieChart.getData().add(new PieChart.Data(
                        cat + String.format(" ($%.0f)", val), val));
            }
            pieSpinner.setVisible(false);
        });
        task.setOnFailed(e -> pieSpinner.setVisible(false));
        daemon(task);
    }

    // =========================================================================
    // Top-5 table
    // =========================================================================

    @SuppressWarnings("unchecked")
    private void setupTop5Table() {
        TableColumn<Object[], String> rankCol = new TableColumn<>("#");
        rankCol.setCellValueFactory(p -> {
            int idx = top5Table.getItems().indexOf(p.getValue()) + 1;
            return new SimpleStringProperty(String.valueOf(idx));
        });
        rankCol.setPrefWidth(35);

        TableColumn<Object[], String> nameCol = new TableColumn<>("Product");
        nameCol.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue()[0] != null ? (String) p.getValue()[0] : "—"));
        nameCol.setPrefWidth(180);

        TableColumn<Object[], String> qtyCol = new TableColumn<>("Units Sold");
        qtyCol.setCellValueFactory(p ->
                new SimpleStringProperty(String.valueOf(p.getValue()[1])));
        qtyCol.setPrefWidth(90);

        TableColumn<Object[], String> revCol = new TableColumn<>("Revenue (USD)");
        revCol.setCellValueFactory(p ->
                new SimpleStringProperty(String.format("$%.2f", (double) p.getValue()[2])));
        revCol.setPrefWidth(110);

        TableColumn<Object[], String> tndCol = new TableColumn<>("Revenue (TND)");
        tndCol.setCellValueFactory(p ->
                new SimpleStringProperty(fx.formatTnd((double) p.getValue()[2])));
        tndCol.setPrefWidth(110);

        top5Table.getColumns().addAll(rankCol, nameCol, qtyCol, revCol, tndCol);
        top5Table.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY);
        top5Table.setPlaceholder(new Label("No sales data yet."));
    }

    private void refreshTop5(List<Object[]> data) {
        List<Object[]> top5 = data.size() > 5 ? data.subList(0, 5) : data;
        top5Table.setItems(FXCollections.observableArrayList(top5));
        top5Table.refresh();
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private void daemon(Task<?> task) {
        Thread t = new Thread(task, "analytics-task");
        t.setDaemon(true);
        t.start();
    }
}
