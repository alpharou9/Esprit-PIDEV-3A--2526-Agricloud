package org.example.controller;

import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.input.MouseEvent;
import javafx.scene.layout.*;
import javafx.stage.FileChooser;
import org.example.MainApp;
import org.example.model.Product;
import org.example.model.User;
import org.example.service.ProductService;
import org.example.session.UserSession;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.util.List;
import java.util.stream.Collectors;

/**
 * UI controller for the Products page.
 *
 * Role behaviour:
 *  FARMER - can add, edit, and delete their OWN products. New products are
 *           submitted with status "pending". The Approve/Reject buttons are hidden.
 *  ADMIN  - sees all products. Approve/Reject buttons appear on each card.
 *           Add/Edit/Delete buttons are visible so admins can manage the catalog.
 *
 * Business rules are enforced in ProductService; this controller only drives the UI.
 */
public class ProductController {

    // -------------------------------------------------------------------------
    // FXML bindings
    // -------------------------------------------------------------------------
    @FXML private StackPane rootStack;
    @FXML private FlowPane  productGrid;
    @FXML private TextField searchField;
    @FXML private ComboBox<String> filterCategoryCombo;
    @FXML private ComboBox<String> filterStatusCombo;
    @FXML private ComboBox<String> sortCombo;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------
    private static final int LOW_STOCK_THRESHOLD = 5;

    private static final List<String> CATEGORIES =
            List.of("All", "Fruits", "Vegetables", "Grains", "Dairy", "Livestock", "Other");
    private static final List<String> UNITS =
            List.of("kg", "g", "lb", "ton", "piece", "dozen", "liter", "gallon");
    private static final List<String> ADMIN_STATUSES =
            List.of("All", "pending", "approved", "rejected", "sold_out");
    private static final List<String> FARMER_STATUSES =
            List.of("All", "pending", "approved", "rejected", "sold_out");
    private static final List<String> SORT_OPTIONS =
            List.of("Name A-Z", "Name Z-A", "Price Low-High", "Price High-Low",
                    "Low Stock First", "Newest First");

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    private final ProductService productService = new ProductService();
    private List<Product> allProducts;
    private MainController mainController;

    // Drawer state
    private StackPane drawerOverlay;
    private Product   editingProduct;

    // Drawer form fields (built programmatically)
    private TextField     nameField;
    private TextArea      descField;
    private TextField     priceField;
    private TextField     qtyField;
    private ComboBox<String> unitCombo;
    private ComboBox<String> catCombo;
    private Label         imagePathLabel;
    private Label         formError;
    private String        pendingImagePath;

    // Listener guard – setupFilters() is called again on role change; don't add twice
    private boolean listenersAttached = false;

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    @FXML
    public void initialize() {
        setupFilters();
        refreshGrid();
    }

    public void setMainController(MainController mc) {
        this.mainController = mc;
    }

    /** Called when the user switches role in Settings. */
    public void onRoleChanged() {
        setupFilters();
        refreshGrid();
    }

    // -------------------------------------------------------------------------
    // Filter / sort setup
    // -------------------------------------------------------------------------

    private void setupFilters() {
        filterCategoryCombo.getItems().setAll(CATEGORIES);
        filterCategoryCombo.setValue("All");

        filterStatusCombo.getItems().setAll(
                UserSession.getInstance().isAdmin() ? ADMIN_STATUSES : FARMER_STATUSES);
        filterStatusCombo.setValue("All");

        sortCombo.getItems().setAll(SORT_OPTIONS);
        sortCombo.setValue("Newest First");

        if (!listenersAttached) {
            searchField.textProperty().addListener((obs, o, n) -> applyFilter());
            filterCategoryCombo.valueProperty().addListener((obs, o, n) -> applyFilter());
            filterStatusCombo.valueProperty().addListener((obs, o, n) -> applyFilter());
            sortCombo.valueProperty().addListener((obs, o, n) -> applyFilter());
            listenersAttached = true;
        }
    }

    // -------------------------------------------------------------------------
    // Data loading
    // -------------------------------------------------------------------------

    public void refreshGrid() {
        try {
            User user = UserSession.getInstance().getCurrentUser();
            if (user.getRole() == User.Role.ADMIN) {
                allProducts = productService.getAllProducts();
            } else {
                allProducts = productService.getFarmerProducts(user.getId());
            }
        } catch (Exception e) {
            allProducts = List.of();
            MainApp.getInstance().showToast("Failed to load products: " + e.getMessage(), "error");
        }
        applyFilter();
    }

    private void applyFilter() {
        if (allProducts == null) return;
        String cat    = filterCategoryCombo.getValue();
        String status = filterStatusCombo.getValue();
        if (cat == null || status == null) return;   // ComboBox is being repopulated
        String search  = searchField.getText().toLowerCase().trim();
        String sort    = sortCombo.getValue();

        List<Product> filtered = allProducts.stream()
                .filter(p -> search.isEmpty() ||
                             p.getName().toLowerCase().contains(search) ||
                             (p.getDescription() != null &&
                              p.getDescription().toLowerCase().contains(search)))
                .filter(p -> "All".equals(cat) || cat.equals(p.getCategory()))
                .filter(p -> "All".equals(status) || status.equals(p.getStatus()))
                .sorted((a, b) -> switch (sort == null ? "" : sort) {
                    case "Name A-Z"       -> a.getName().compareToIgnoreCase(b.getName());
                    case "Name Z-A"       -> b.getName().compareToIgnoreCase(a.getName());
                    case "Price Low-High" -> Double.compare(a.getPrice(), b.getPrice());
                    case "Price High-Low" -> Double.compare(b.getPrice(), a.getPrice());
                    case "Low Stock First"-> Integer.compare(a.getQuantity(), b.getQuantity());
                    default               -> {
                        if (b.getCreatedAt() == null) yield -1;
                        if (a.getCreatedAt() == null) yield 1;
                        yield b.getCreatedAt().compareTo(a.getCreatedAt());
                    }
                })
                .collect(Collectors.toList());

        productGrid.getChildren().clear();
        if (filtered.isEmpty()) {
            Label empty = new Label("No products found.");
            empty.setStyle("-fx-text-fill: #9E9E9E; -fx-font-size: 13px;");
            productGrid.getChildren().add(empty);
        } else {
            for (Product p : filtered) {
                productGrid.getChildren().add(buildProductCard(p));
            }
        }
    }

    // -------------------------------------------------------------------------
    // Product card builder
    // -------------------------------------------------------------------------

    private Node buildProductCard(Product p) {
        VBox card = new VBox(6);
        card.setPrefWidth(162);
        card.setMaxWidth(162);
        card.setAlignment(Pos.TOP_CENTER);
        card.setStyle(
            "-fx-background-color: white;" +
            "-fx-background-radius: 10;" +
            "-fx-border-color: #E8E8E8;" +
            "-fx-border-radius: 10;" +
            "-fx-border-width: 1;" +
            "-fx-effect: dropshadow(gaussian,rgba(0,0,0,0.08),8,0,0,2);" +
            "-fx-padding: 12;");

        // ── Image / emoji ──────────────────────────────────────────────────────
        StackPane imgBox = new StackPane();
        imgBox.setPrefSize(138, 100);
        imgBox.setMaxWidth(Double.MAX_VALUE);
        imgBox.setAlignment(Pos.CENTER);
        imgBox.setStyle("-fx-background-color: #F5F5F5; -fx-background-radius: 7;");

        File imgFile = (p.getImage() != null && !p.getImage().isBlank())
                ? new File(p.getImage()) : null;
        if (imgFile != null && imgFile.exists()) {
            ImageView iv = new ImageView(
                    new Image(imgFile.toURI().toString(), 138, 100, true, true));
            iv.setFitWidth(138);
            iv.setFitHeight(100);
            iv.setPreserveRatio(true);
            imgBox.getChildren().add(iv);
        } else {
            Label emoji = new Label(categoryEmoji(p.getCategory()));
            emoji.setStyle("-fx-font-size: 38px;");
            imgBox.getChildren().add(emoji);
        }

        // ── Name ───────────────────────────────────────────────────────────────
        Label name = new Label(p.getName());
        name.setStyle("-fx-font-weight: bold; -fx-font-size: 12px; -fx-text-alignment: center;");
        name.setWrapText(true);
        name.setMaxWidth(138);

        // ── Category ───────────────────────────────────────────────────────────
        Label cat = new Label(p.getCategory() != null ? p.getCategory() : "");
        cat.setStyle("-fx-font-size: 10px; -fx-text-fill: #9E9E9E;");

        // ── Price ──────────────────────────────────────────────────────────────
        Label price = new Label(String.format("$%.2f / %s", p.getPrice(), p.getUnit()));
        price.setStyle("-fx-font-size: 11px; -fx-font-weight: bold; -fx-text-fill: #2E7D32;");

        // ── Stock ──────────────────────────────────────────────────────────────
        boolean lowStock = p.getQuantity() > 0 && p.getQuantity() <= LOW_STOCK_THRESHOLD;
        String stockText = "Stock: " + p.getQuantity() + " " + p.getUnit()
                + (lowStock ? " ⚠" : "");
        Label stock = new Label(stockText);
        stock.setStyle("-fx-font-size: 10px; -fx-text-fill: " + (lowStock ? "#E65100;" : "#555;"));

        // ── Status badge ───────────────────────────────────────────────────────
        Label badge = new Label(p.getStatus() != null ? p.getStatus() : "");
        badge.getStyleClass().addAll("status-badge", "status-" + p.getStatus());

        // ── Action buttons ─────────────────────────────────────────────────────
        HBox actions = new HBox(5);
        actions.setAlignment(Pos.CENTER);
        // Prevent button clicks from bubbling up to the card's detail-panel handler
        actions.addEventHandler(MouseEvent.MOUSE_CLICKED, MouseEvent::consume);

        User user = UserSession.getInstance().getCurrentUser();
        boolean canEditDelete = user.getRole() == User.Role.ADMIN
                || p.getUserId() == user.getId();

        if (canEditDelete) {
            Button edit = new Button("Edit");
            edit.getStyleClass().add("btn-secondary");
            edit.setStyle("-fx-font-size: 10px; -fx-padding: 3 7;");
            edit.setOnAction(e -> openDrawer(p));

            Button delete = new Button("Del");
            delete.getStyleClass().add("btn-danger");
            delete.setStyle("-fx-font-size: 10px; -fx-padding: 3 7;");
            delete.setOnAction(e -> confirmDelete(p));

            actions.getChildren().addAll(edit, delete);
        }

        if (user.getRole() == User.Role.ADMIN && "pending".equals(p.getStatus())) {
            Button approve = new Button("✓");
            approve.getStyleClass().add("btn-success");
            approve.setStyle("-fx-font-size: 11px; -fx-padding: 3 7;");
            approve.setOnAction(e -> approveProduct(p));

            Button reject = new Button("✗");
            reject.getStyleClass().add("btn-danger");
            reject.setStyle("-fx-font-size: 11px; -fx-padding: 3 7;");
            reject.setOnAction(e -> rejectProduct(p));

            actions.getChildren().addAll(approve, reject);
        }

        card.getChildren().addAll(imgBox, name, cat, price, stock, badge, actions);

        // Click anywhere on the card (not on buttons) → show detail panel
        card.setStyle(card.getStyle() + "-fx-cursor: hand;");
        card.setOnMouseClicked(e -> openDetailPanel(p));

        return card;
    }

    // -------------------------------------------------------------------------
    // Product detail panel (read-only overlay)
    // -------------------------------------------------------------------------

    private void openDetailPanel(Product p) {
        StackPane overlay = new StackPane();
        overlay.setStyle("-fx-background-color: rgba(0,0,0,0.4);");
        overlay.setAlignment(Pos.CENTER_RIGHT);
        overlay.setOnMouseClicked(e -> {
            if (e.getTarget() == overlay) rootStack.getChildren().remove(overlay);
        });

        ScrollPane scroll = new ScrollPane();
        scroll.setFitToWidth(true);
        scroll.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        scroll.setMaxWidth(400);
        scroll.setPrefWidth(400);
        scroll.setMaxHeight(Double.MAX_VALUE);
        scroll.setStyle(
            "-fx-background-color: white;" +
            "-fx-background-radius: 8;" +
            "-fx-effect: dropshadow(gaussian, rgba(0,0,0,0.35), 24, 0, -4, 0);");

        VBox panel = new VBox(14);
        panel.setPadding(new Insets(24));
        panel.setStyle("-fx-background-color: white; -fx-background-radius: 8;");

        // ── Header ─────────────────────────────────────────────────────────
        HBox header = new HBox(8);
        header.setAlignment(Pos.CENTER_LEFT);
        Label title = new Label("Product Details");
        title.setStyle("-fx-font-size: 18px; -fx-font-weight: bold;");
        Region sp = new Region();
        HBox.setHgrow(sp, Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-font-size: 16px; -fx-cursor: hand; -fx-padding: 0 4;");
        closeBtn.setOnAction(e -> rootStack.getChildren().remove(overlay));
        header.getChildren().addAll(title, sp, closeBtn);

        // ── Image ───────────────────────────────────────────────────────────
        StackPane imgBox = new StackPane();
        imgBox.setPrefHeight(160);
        imgBox.setMaxWidth(Double.MAX_VALUE);
        imgBox.setStyle("-fx-background-color: #F5F5F5; -fx-background-radius: 8;");
        File imgFile = (p.getImage() != null && !p.getImage().isBlank())
                ? new File(p.getImage()) : null;
        if (imgFile != null && imgFile.exists()) {
            ImageView iv = new ImageView(
                    new Image(imgFile.toURI().toString(), 350, 160, true, true));
            iv.setFitWidth(350);
            iv.setFitHeight(160);
            iv.setPreserveRatio(true);
            imgBox.getChildren().add(iv);
        } else {
            Label emoji = new Label(categoryEmoji(p.getCategory()));
            emoji.setStyle("-fx-font-size: 64px;");
            imgBox.getChildren().add(emoji);
        }

        // ── Status badge ────────────────────────────────────────────────────
        Label badge = new Label(p.getStatus() != null ? p.getStatus().toUpperCase() : "");
        badge.getStyleClass().addAll("status-badge", "status-" + p.getStatus());

        // ── Detail rows ─────────────────────────────────────────────────────
        VBox details = new VBox(10);
        details.getChildren().addAll(
            detailRow("Name",        p.getName()),
            detailRow("Category",    p.getCategory() != null ? p.getCategory() : "—"),
            detailRow("Price",       String.format("$%.2f / %s", p.getPrice(), p.getUnit())),
            detailRow("Stock",       p.getQuantity() + " " + p.getUnit()
                                     + (p.getQuantity() <= LOW_STOCK_THRESHOLD && p.getQuantity() > 0 ? "  ⚠ Low" : ""))
        );

        if (p.getDescription() != null && !p.getDescription().isBlank()) {
            Label descLbl  = new Label("Description");
            descLbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #9E9E9E;");
            Label descVal  = new Label(p.getDescription());
            descVal.setWrapText(true);
            descVal.setStyle("-fx-font-size: 13px;");
            details.getChildren().addAll(descLbl, descVal);
        }

        if (p.getCreatedAt() != null) {
            details.getChildren().add(
                detailRow("Added", p.getCreatedAt().toLocalDate().toString()));
        }

        // ── Actions (Edit shortcut) ──────────────────────────────────────────
        HBox footerBtns = new HBox(8);
        footerBtns.setAlignment(Pos.CENTER_RIGHT);
        User user = UserSession.getInstance().getCurrentUser();
        boolean canEdit = user.getRole() == User.Role.ADMIN || p.getUserId() == user.getId();
        if (canEdit) {
            Button editBtn = new Button("Edit Product");
            editBtn.getStyleClass().add("btn-primary");
            editBtn.setOnAction(e -> {
                rootStack.getChildren().remove(overlay);
                openDrawer(p);
            });
            footerBtns.getChildren().add(editBtn);
        }
        Button dismissBtn = new Button("Close");
        dismissBtn.getStyleClass().add("btn-secondary");
        dismissBtn.setOnAction(e -> rootStack.getChildren().remove(overlay));
        footerBtns.getChildren().add(dismissBtn);

        panel.getChildren().addAll(header, imgBox, badge, details, footerBtns);
        scroll.setContent(panel);
        overlay.getChildren().add(scroll);
        rootStack.getChildren().add(overlay);
    }

    private Node detailRow(String label, String value) {
        VBox row = new VBox(2);
        Label lbl = new Label(label);
        lbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #9E9E9E;");
        Label val = new Label(value);
        val.setStyle("-fx-font-size: 13px; -fx-font-weight: bold;");
        row.getChildren().addAll(lbl, val);
        return row;
    }

    /** Returns a representative emoji for a product category. */
    private String categoryEmoji(String cat) {
        if (cat == null) return "🌾";
        return switch (cat) {
            case "Fruits"     -> "🍎";
            case "Vegetables" -> "🥦";
            case "Grains"     -> "🌾";
            case "Dairy"      -> "🥛";
            case "Livestock"  -> "🐄";
            default           -> "📦";
        };
    }

    // -------------------------------------------------------------------------
    // FXML actions
    // -------------------------------------------------------------------------

    @FXML
    void onAddProduct() {
        openDrawer(null);
    }

    // -------------------------------------------------------------------------
    // Approve / Reject (admin only)
    // -------------------------------------------------------------------------

    private void approveProduct(Product p) {
        try {
            productService.approveProduct(p.getId(), UserSession.getInstance().getCurrentUser());
            MainApp.getInstance().showToast("Product approved: " + p.getName(), "success");
            refreshGrid();
            if (mainController != null) mainController.notifyProductsChanged();
        } catch (Exception e) {
            MainApp.getInstance().showToast(e.getMessage(), "error");
        }
    }

    private void rejectProduct(Product p) {
        try {
            productService.rejectProduct(p.getId(), UserSession.getInstance().getCurrentUser());
            MainApp.getInstance().showToast("Product rejected: " + p.getName(), "info");
            refreshGrid();
            if (mainController != null) mainController.notifyProductsChanged();
        } catch (Exception e) {
            MainApp.getInstance().showToast(e.getMessage(), "error");
        }
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    private void confirmDelete(Product p) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle("Delete Product");
        alert.setHeaderText("Delete \"" + p.getName() + "\"?");
        alert.setContentText("This action cannot be undone.");
        alert.showAndWait().ifPresent(btn -> {
            if (btn == ButtonType.OK) {
                try {
                    productService.deleteProduct(p.getId(),
                            UserSession.getInstance().getCurrentUser());
                    MainApp.getInstance().showToast("Product deleted.", "info");
                    refreshGrid();
                    if (mainController != null) mainController.notifyProductsChanged();
                } catch (Exception e) {
                    MainApp.getInstance().showToast(e.getMessage(), "error");
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Drawer - Add / Edit form
    // -------------------------------------------------------------------------

    private void openDrawer(Product existing) {
        editingProduct    = existing;
        pendingImagePath  = existing != null ? existing.getImage() : null;

        // Build overlay (semi-transparent backdrop)
        drawerOverlay = new StackPane();
        drawerOverlay.setStyle("-fx-background-color: rgba(0,0,0,0.4);");
        drawerOverlay.setAlignment(Pos.CENTER_RIGHT);
        drawerOverlay.setOnMouseClicked(e -> {
            if (e.getTarget() == drawerOverlay) closeDrawer();
        });

        // Drawer panel - inline style avoids CSS variable resolution issues
        VBox panel = new VBox(14);
        panel.setMaxWidth(380);
        panel.setPrefWidth(380);
        panel.setStyle(
            "-fx-background-color: white;" +
            "-fx-background-radius: 8;" +
            "-fx-padding: 24;" +
            "-fx-effect: dropshadow(gaussian, rgba(0,0,0,0.35), 24, 0, -4, 0);");

        // Title
        Label title = new Label(existing == null ? "Add Product" : "Edit Product");
        title.setStyle("-fx-font-size: 18px; -fx-font-weight: bold;");

        // Form fields
        nameField = new TextField(existing != null ? existing.getName() : "");
        nameField.setPromptText("Product name *");

        descField = new TextArea(existing != null ? existing.getDescription() : "");
        descField.setPromptText("Description");
        descField.setPrefRowCount(3);
        descField.setWrapText(true);

        priceField = new TextField(existing != null ? String.valueOf(existing.getPrice()) : "");
        priceField.setPromptText("Price *");

        qtyField = new TextField(existing != null ? String.valueOf(existing.getQuantity()) : "");
        qtyField.setPromptText("Quantity *");

        unitCombo = new ComboBox<>();
        unitCombo.getItems().addAll(UNITS);
        unitCombo.setValue(existing != null ? existing.getUnit() : "kg");
        unitCombo.setMaxWidth(Double.MAX_VALUE);

        catCombo = new ComboBox<>();
        catCombo.getItems().addAll(CATEGORIES.subList(1, CATEGORIES.size()));
        catCombo.setValue(existing != null ? existing.getCategory() : "Fruits");
        catCombo.setMaxWidth(Double.MAX_VALUE);

        // Image picker
        imagePathLabel = new Label(
                pendingImagePath != null ? new File(pendingImagePath).getName() : "No image selected");
        imagePathLabel.setStyle("-fx-text-fill: #757575; -fx-font-size: 11px;");
        Button pickImage = new Button("Choose Image");
        pickImage.getStyleClass().add("btn-secondary");
        pickImage.setOnAction(e -> pickImage());

        formError = new Label("");
        formError.setStyle("-fx-text-fill: #F44336; -fx-font-size: 11px;");
        formError.setWrapText(true);

        // Save / Cancel
        Button save   = new Button(existing == null ? "Add Product" : "Save Changes");
        Button cancel = new Button("Cancel");
        save.getStyleClass().add("btn-primary");
        cancel.getStyleClass().add("btn-secondary");
        save.setOnAction(e -> submitForm());
        cancel.setOnAction(e -> closeDrawer());
        save.setMaxWidth(Double.MAX_VALUE);
        cancel.setMaxWidth(Double.MAX_VALUE);

        panel.getChildren().addAll(
                title,
                labeledField("Name *", nameField),
                labeledField("Description", descField),
                labeledField("Price ($) *", priceField),
                labeledField("Quantity *", qtyField),
                labeledField("Unit", unitCombo),
                labeledField("Category", catCombo),
                new VBox(4, new Label("Image"), imagePathLabel, pickImage),
                formError,
                save, cancel);

        drawerOverlay.getChildren().add(panel);
        rootStack.getChildren().add(drawerOverlay);
    }

    private Node labeledField(String label, Node field) {
        VBox box = new VBox(4);
        Label lbl = new Label(label);
        lbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #757575;");
        box.getChildren().addAll(lbl, field);
        return box;
    }

    private void closeDrawer() {
        rootStack.getChildren().remove(drawerOverlay);
        drawerOverlay = null;
    }

    private void pickImage() {
        FileChooser fc = new FileChooser();
        fc.setTitle("Select Product Image");
        fc.getExtensionFilters().add(
                new FileChooser.ExtensionFilter("Images",
                        "*.png","*.jpg","*.jpeg","*.gif","*.bmp","*.webp"));
        File chosen = fc.showOpenDialog(rootStack.getScene().getWindow());
        if (chosen == null) return;
        if (chosen.length() > 5 * 1024 * 1024) {
            formError.setText("Image must be smaller than 5 MB.");
            return;
        }
        try {
            Path dest = Paths.get("uploads", "products",
                    System.currentTimeMillis() + "_" + chosen.getName());
            Files.createDirectories(dest.getParent());
            Files.copy(chosen.toPath(), dest, StandardCopyOption.REPLACE_EXISTING);
            pendingImagePath = dest.toString();
            imagePathLabel.setText(chosen.getName());
            formError.setText("");
        } catch (IOException e) {
            formError.setText("Failed to copy image: " + e.getMessage());
        }
    }

    private void submitForm() {
        formError.setText("");
        String name = nameField.getText().trim();
        if (name.length() < 3 || name.length() > 100) {
            formError.setText("Name must be 3-100 characters.");
            return;
        }
        double price;
        try {
            price = Double.parseDouble(priceField.getText().trim());
            if (price <= 0) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            formError.setText("Price must be a positive number.");
            return;
        }
        int qty;
        try {
            qty = Integer.parseInt(qtyField.getText().trim());
            if (qty < 0) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            formError.setText("Quantity must be a non-negative integer.");
            return;
        }
        String desc = descField.getText().trim();
        if (desc.length() > 2000) {
            formError.setText("Description must be 2000 characters or fewer.");
            return;
        }

        Product p = editingProduct != null ? editingProduct : new Product();
        p.setName(name);
        p.setDescription(desc);
        p.setPrice(price);
        p.setQuantity(qty);
        p.setUnit(unitCombo.getValue());
        p.setCategory(catCombo.getValue());
        p.setImage(pendingImagePath);

        try {
            User user = UserSession.getInstance().getCurrentUser();
            if (editingProduct == null) {
                productService.addProduct(p, user);
                MainApp.getInstance().showToast("Product added (pending approval).", "success");
            } else {
                productService.updateProduct(p, user);
                MainApp.getInstance().showToast("Product updated.", "success");
            }
            closeDrawer();
            refreshGrid();
            if (mainController != null) mainController.notifyProductsChanged();
        } catch (Exception e) {
            formError.setText(e.getMessage());
        }
    }
}
