package org.example.controller;

import javafx.beans.property.ReadOnlyObjectWrapper;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.*;
import org.example.MainApp;
import org.example.model.Order;
import org.example.model.OrderDetail;
import org.example.model.Product;
import org.example.model.User;
import javafx.concurrent.Task;
import org.example.model.Notification;
import org.example.service.ExchangeRateService;
import org.example.service.NotificationService;
import org.example.service.OrderService;
import org.example.service.ProductService;
import org.example.service.SmsService;
import org.example.session.UserSession;

import java.io.File;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import javafx.scene.input.MouseEvent;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.PDPage;
import org.apache.pdfbox.pdmodel.PDPageContentStream;
import org.apache.pdfbox.pdmodel.common.PDRectangle;
import org.apache.pdfbox.pdmodel.font.PDType1Font;

/**
 * Controller for orders-view.fxml.
 *
 * Tab 1 "New Order"  – product catalog (paginated) + cart + place order.
 * Tab 2 "Manage Orders" – orders table (paginated) + detail rows + edit/delete.
 */
public class OrderController {

    // =========================================================================
    // FXML bindings
    // =========================================================================

    @FXML private StackPane rootStack;
    @FXML private TabPane   tabPane;
    @FXML private Tab       newOrderTab;

    // ── Tab 1: catalog ──
    @FXML private FlowPane  productGrid;
    @FXML private TextField searchField;
    @FXML private Button    prevProductBtn;
    @FXML private Button    nextProductBtn;
    @FXML private Label     productPageLabel;

    // ── Tab 1: cart ──
    @FXML private TableView<CartItem> cartTable;
    @FXML private Label      totalLabel;
    @FXML private Label      totalTndLabel;
    @FXML private TextArea   notesField;
    @FXML private DatePicker deliveryDatePicker;
    @FXML private CheckBox   smsCheckBox;
    @FXML private TextField  phoneField;
    @FXML private Label      orderFormError;

    // ── Tab 2: dashboard ──
    @FXML private TableView<Order>       ordersTable;
    @FXML private Button                 prevOrderBtn;
    @FXML private Button                 nextOrderBtn;
    @FXML private Label                  orderPageLabel;
    @FXML private TableView<OrderDetail> detailsTable;

    // =========================================================================
    // Constants
    // =========================================================================

    private static final int    PRODUCT_PAGE_SIZE = 9;
    private static final int    ORDER_PAGE_SIZE   = 10;
    private static final DateTimeFormatter DATE_FMT =
            DateTimeFormatter.ofPattern("MMM dd, yyyy");

    // =========================================================================
    // State
    // =========================================================================

    private final ObservableList<CartItem> cartItems = FXCollections.observableArrayList();

    private int productPage       = 0;
    private int productTotalPages = 1;
    private int orderPage         = 0;
    private int orderTotalPages   = 1;

    private MainController mainController;

    // Drawer (edit order)
    private StackPane drawerOverlay;

    private final OrderService        orderService   = new OrderService();
    private final ProductService      productService = new ProductService();
    private final SmsService          smsService     = new SmsService();
    private final NotificationService notifService   = NotificationService.getInstance();

    // =========================================================================
    // Initialization
    // =========================================================================

    @FXML
    public void initialize() {
        setupCartTable();
        setupOrdersTable();
        setupDetailsTable();
        applyRoleVisibility();
        loadProductPage();
        loadOrderPage();
    }

    public void setMainController(MainController mc) { this.mainController = mc; }

    /** Called by MainController after a role switch. */
    public void onRoleChanged() {
        applyRoleVisibility();
        productPage = 0;
        orderPage   = 0;
        loadProductPage();
        loadOrderPage();
    }

    /** Called by MainController when products change (stock update). */
    public void refreshProducts() {
        productPage = 0;
        loadProductPage();
    }

    /** Called by MainController when navigating to the Orders page. */
    public void refreshOrders() {
        orderPage = 0;
        loadOrderPage();
    }

    // =========================================================================
    // Role visibility
    // =========================================================================

    private void applyRoleVisibility() {
        boolean isFarmer = UserSession.getInstance().isFarmer();
        // New Order tab is only meaningful for farmers
        newOrderTab.setDisable(!isFarmer);
        if (!isFarmer && tabPane != null) {
            tabPane.getSelectionModel().select(1);
        }
    }

    // =========================================================================
    // ── TAB 1: PRODUCT CATALOG ────────────────────────────────────────────────
    // =========================================================================

    private void loadProductPage() {
        String search = searchField == null ? "" : searchField.getText().trim();
        try {
            int total = productService.countCatalog(search);
            productTotalPages = Math.max(1, (int) Math.ceil((double) total / PRODUCT_PAGE_SIZE));
            if (productPage >= productTotalPages) productPage = productTotalPages - 1;

            List<Product> products = productService.getCatalogPage(
                    search, PRODUCT_PAGE_SIZE, productPage * PRODUCT_PAGE_SIZE);

            productGrid.getChildren().clear();
            if (products.isEmpty()) {
                VBox empty = new VBox(8);
                empty.setAlignment(Pos.CENTER);
                empty.setPadding(new Insets(40));
                Label icon = new Label("🛒");
                icon.setStyle("-fx-font-size: 40px;");
                Label msg = new Label(search.isEmpty()
                        ? "No products available yet.\nAdd products from the Products page."
                        : "No products match \"" + search + "\".");
                msg.setStyle("-fx-text-fill: #757575; -fx-font-size: 13px; -fx-text-alignment: center;");
                msg.setWrapText(true);
                msg.setMaxWidth(300);
                empty.getChildren().addAll(icon, msg);
                productGrid.getChildren().add(empty);
            } else {
                for (Product p : products) {
                    productGrid.getChildren().add(createProductCard(p));
                }
            }
            productPageLabel.setText("Page " + (productPage + 1) + " / " + productTotalPages);
            prevProductBtn.setDisable(productPage == 0);
            nextProductBtn.setDisable(productPage >= productTotalPages - 1);

        } catch (Exception e) {
            System.err.println("[OrderController] loadProductPage error: " + e.getMessage());
            e.printStackTrace();
            MainApp.getInstance().showToast("Failed to load products: " + e.getMessage(), "error");
        }
    }

    @FXML void onSearchProducts()    { productPage = 0; loadProductPage(); }
    @FXML void onPrevProductPage()   { if (productPage > 0) { productPage--; loadProductPage(); } }
    @FXML void onNextProductPage()   { if (productPage < productTotalPages - 1) { productPage++; loadProductPage(); } }

    // ── Product card ─────────────────────────────────────────────────────────

    private static final int CARD_W   = 185;
    private static final int CARD_IMG = 120;

    private Node createProductCard(Product p) {
        // A product is orderable if it has stock (rejected products never reach
        // this method – the catalog query excludes them).
        boolean orderable = p.getQuantity() > 0;
        boolean pending   = "pending".equals(p.getStatus());

        // ── Root card ────────────────────────────────────────────────────────
        VBox card = new VBox(0);
        card.getStyleClass().add("product-card");
        card.setPrefWidth(CARD_W);
        card.setMinWidth(CARD_W);
        card.setMaxWidth(CARD_W);

        if (orderable) {
            card.setOnMouseClicked(e -> onAddToCart(p));
            card.setOnMouseEntered(e -> card.getStyleClass().add("product-card-selected"));
            card.setOnMouseExited(e -> card.getStyleClass().remove("product-card-selected"));
        } else {
            card.setOpacity(0.6);
        }

        // ── Image / icon area ────────────────────────────────────────────────
        StackPane imgArea = new StackPane();
        imgArea.getStyleClass().add("product-card-image");
        imgArea.setPrefHeight(CARD_IMG);
        imgArea.setMinHeight(CARD_IMG);
        imgArea.setMaxHeight(CARD_IMG);
        imgArea.setPrefWidth(CARD_W);

        if (p.getImage() != null && !p.getImage().isBlank()) {
            File f = new File(p.getImage());
            if (f.exists()) {
                ImageView iv = new ImageView(
                        new Image(f.toURI().toString(), CARD_W, CARD_IMG, true, true));
                iv.setFitWidth(CARD_W);
                iv.setFitHeight(CARD_IMG);
                iv.setPreserveRatio(true);
                imgArea.getChildren().add(iv);
            } else {
                imgArea.getChildren().add(categoryIcon(p.getCategory()));
            }
        } else {
            imgArea.getChildren().add(categoryIcon(p.getCategory()));
        }

        // ── Info area ────────────────────────────────────────────────────────
        VBox info = new VBox(4);
        info.getStyleClass().add("product-card-info");
        info.setPadding(new Insets(10, 12, 6, 12));

        String cat = p.getCategory() != null ? p.getCategory() : "Other";
        Label catChip = new Label(cat);
        catChip.getStyleClass().addAll("category-chip", "category-" + cat.toLowerCase());

        Label name = new Label(p.getName());
        name.getStyleClass().add("product-card-name");
        name.setWrapText(true);
        name.setMaxWidth(CARD_W - 24.0);

        Label price = new Label(String.format("$%.2f / %s", p.getPrice(), p.getUnit()));
        price.getStyleClass().add("product-card-price");

        // Stock hint  (orange badge when still pending review)
        Label stockLbl = new Label("Stock: " + p.getQuantity());
        stockLbl.setStyle("-fx-font-size: 10px; -fx-text-fill: #757575;");

        if (pending) {
            Label pendingBadge = new Label("Pending review");
            pendingBadge.setStyle(
                "-fx-font-size: 9px; -fx-text-fill: white; -fx-background-color: #FB8C00;" +
                "-fx-background-radius: 3; -fx-padding: 1 5 1 5; -fx-font-weight: bold;");
            info.getChildren().addAll(catChip, name, price, stockLbl, pendingBadge);
        } else {
            info.getChildren().addAll(catChip, name, price, stockLbl);
        }

        // ── Button area ──────────────────────────────────────────────────────
        VBox btnArea = new VBox();
        btnArea.getStyleClass().add("card-actions");
        btnArea.setPadding(new Insets(8, 12, 12, 12));

        Button addBtn = new Button(orderable ? "+ Add to Cart" : "Out of Stock");
        addBtn.getStyleClass().add("btn-primary");
        addBtn.setMaxWidth(Double.MAX_VALUE);
        addBtn.setDisable(!orderable);
        // Consume the raw mouse event on the button so it does NOT bubble up
        // to the card's onMouseClicked handler — the ActionEvent still fires.
        addBtn.addEventFilter(MouseEvent.MOUSE_CLICKED, MouseEvent::consume);
        addBtn.setOnAction(e -> onAddToCart(p));
        btnArea.getChildren().add(addBtn);

        card.getChildren().addAll(imgArea, info, btnArea);
        return card;
    }

    /** Large emoji icon shown when a product has no image file. */
    private Label categoryIcon(String category) {
        String icon = switch (category != null ? category : "") {
            case "Fruits"     -> "🍎";
            case "Vegetables" -> "🥦";
            case "Grains"     -> "🌾";
            case "Dairy"      -> "🥛";
            case "Livestock"  -> "🐄";
            default           -> "🌿";
        };
        Label lbl = new Label(icon);
        lbl.getStyleClass().add("image-placeholder-text");
        return lbl;
    }

    // =========================================================================
    // ── TAB 1: CART ──────────────────────────────────────────────────────────
    // =========================================================================

    @SuppressWarnings("unchecked")
    private void setupCartTable() {
        // Image
        TableColumn<CartItem, CartItem> imgCol = new TableColumn<>("");
        imgCol.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        imgCol.setCellFactory(col -> new TableCell<>() {
            private final ImageView iv = new ImageView();
            { iv.setFitWidth(40); iv.setFitHeight(40); iv.setPreserveRatio(true); }
            @Override protected void updateItem(CartItem item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) { setGraphic(null); return; }
                String img = item.getProduct().getImage();
                if (img != null && !img.isBlank()) {
                    File f = new File(img);
                    if (f.exists()) {
                        iv.setImage(new Image(f.toURI().toString(), 40, 40, true, true));
                        setGraphic(iv);
                        return;
                    }
                }
                Label lbl = new Label("—");
                lbl.setStyle("-fx-text-fill: #9E9E9E;");
                setGraphic(lbl);
            }
        });
        imgCol.setPrefWidth(55);
        imgCol.setSortable(false);

        // Name
        TableColumn<CartItem, String> nameCol = new TableColumn<>("Product");
        nameCol.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue().getProduct().getName()));
        nameCol.setPrefWidth(120);

        // Unit price
        TableColumn<CartItem, String> priceCol = new TableColumn<>("Unit Price");
        priceCol.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getProduct().getPrice())));
        priceCol.setPrefWidth(75);

        // Qty  (– label +  buttons)
        TableColumn<CartItem, CartItem> qtyCol = new TableColumn<>("Qty");
        qtyCol.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        qtyCol.setCellFactory(col -> new TableCell<>() {
            private final Button minus = new Button("−");
            private final Label  num   = new Label();
            private final Button plus  = new Button("+");
            private final HBox   box   = new HBox(4, minus, num, plus);
            {
                box.setAlignment(Pos.CENTER);
                minus.setStyle("-fx-min-width: 24; -fx-min-height: 24; -fx-padding: 0;");
                plus.setStyle ("-fx-min-width: 24; -fx-min-height: 24; -fx-padding: 0;");
                minus.setOnAction(e -> {
                    CartItem ci = getTableView().getItems().get(getIndex());
                    if (ci.getQuantity() > 1) {
                        ci.setQuantity(ci.getQuantity() - 1);
                    } else {
                        cartItems.remove(ci);
                    }
                    getTableView().refresh();
                    updateTotals();
                });
                plus.setOnAction(e -> {
                    CartItem ci = getTableView().getItems().get(getIndex());
                    ci.setQuantity(ci.getQuantity() + 1);
                    getTableView().refresh();
                    updateTotals();
                });
            }
            @Override protected void updateItem(CartItem ci, boolean empty) {
                super.updateItem(ci, empty);
                if (empty || ci == null) { setGraphic(null); return; }
                num.setText(String.valueOf(ci.getQuantity()));
                setGraphic(box);
            }
        });
        qtyCol.setPrefWidth(90);
        qtyCol.setSortable(false);

        // Subtotal
        TableColumn<CartItem, String> subCol = new TableColumn<>("Subtotal");
        subCol.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getSubtotal())));
        subCol.setPrefWidth(75);

        // Remove
        TableColumn<CartItem, CartItem> removeCol = new TableColumn<>("");
        removeCol.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        removeCol.setCellFactory(col -> new TableCell<>() {
            private final Button btn = new Button("✕");
            { btn.getStyleClass().add("btn-danger");
              btn.setStyle("-fx-min-width: 28; -fx-min-height: 24; -fx-padding: 0 6;");
              btn.setOnAction(e -> {
                  cartItems.remove(getTableView().getItems().get(getIndex()));
                  updateTotals();
              }); }
            @Override protected void updateItem(CartItem ci, boolean empty) {
                super.updateItem(ci, empty);
                setGraphic(empty || ci == null ? null : btn);
            }
        });
        removeCol.setPrefWidth(45);
        removeCol.setSortable(false);

        cartTable.getColumns().addAll(imgCol, nameCol, priceCol, qtyCol, subCol, removeCol);
        cartTable.setItems(cartItems);
        cartTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY);
        cartTable.setFixedCellSize(50);
        cartTable.setPlaceholder(new Label("No items. Click \"Add to Order\" on a product."));
    }

    private void onAddToCart(Product p) {
        for (CartItem ci : cartItems) {
            if (ci.getProduct().getId() == p.getId()) {
                ci.setQuantity(ci.getQuantity() + 1);
                cartTable.refresh();
                updateTotals();
                return;
            }
        }
        cartItems.add(new CartItem(p, 1));
        updateTotals();
    }

    private void updateTotals() {
        double total = cartItems.stream().mapToDouble(CartItem::getSubtotal).sum();
        totalLabel.setText(String.format("$%.2f", total));
        ExchangeRateService fx = ExchangeRateService.getInstance();
        if (total > 0) {
            totalTndLabel.setText("≈ " + fx.formatTnd(total));
        } else {
            totalTndLabel.setText("");
        }
    }

    @FXML void onClearCart() {
        cartItems.clear();
        updateTotals();
    }

    /** Enables / disables the phone field based on the SMS checkbox state. */
    @FXML
    void onSmsToggle() {
        boolean checked = smsCheckBox.isSelected();
        phoneField.setDisable(!checked);
        if (!checked) phoneField.clear();
    }

    @FXML
    void onPlaceOrder() {
        orderFormError.setText("");
        if (cartItems.isEmpty()) {
            orderFormError.setText("Add at least one product to the order.");
            return;
        }

        boolean wantSms = smsCheckBox.isSelected();
        String  phone   = wantSms ? phoneField.getText().trim() : "";
        if (wantSms && phone.isBlank()) {
            orderFormError.setText("Enter a phone number to receive SMS, or uncheck the SMS option.");
            return;
        }

        // Snapshot everything we need before clearing the cart
        int    itemCount = cartItems.size();
        double cartTotal = cartItems.stream().mapToDouble(CartItem::getSubtotal).sum();
        String firstItem = cartItems.get(0).getProduct().getName();

        Order meta = new Order();
        String notes = notesField.getText() == null ? "" : notesField.getText().trim();
        meta.setNotes(notes.isBlank() ? null : notes);
        meta.setDeliveryDate(deliveryDatePicker.getValue());

        List<OrderDetail> details = new ArrayList<>();
        for (CartItem ci : cartItems) {
            OrderDetail d = new OrderDetail();
            d.setProductId(ci.getProduct().getId());
            d.setQuantity(ci.getQuantity());
            details.add(d);
        }

        User requester = UserSession.getInstance().getCurrentUser();

        // ── Run all DB work on a background thread (no UI freeze) ───────────
        Task<List<Order>> task = new Task<>() {
            @Override
            protected List<Order> call() throws Exception {
                return orderService.createOrder(meta, details, requester);
            }
        };

        task.setOnSucceeded(e -> {
            List<Order> inserted = task.getValue();

            // Clear cart / form immediately — UI feels instant
            cartItems.clear();
            updateTotals();
            notesField.clear();
            deliveryDatePicker.setValue(null);
            loadProductPage();
            loadOrderPage();
            if (mainController != null) mainController.notifyProductsChanged();

            // Push in-app notifications (non-blocking: DB insert is background)
            for (Order row : inserted) {
                Notification n = new Notification(
                        Notification.Type.ORDER_STATUS,
                        "Order Placed",
                        "Order #" + row.getId() + " for "
                                + (row.getProductName() != null ? row.getProductName() : "product")
                                + " is now pending.",
                        row.getId(), "ORDER");
                notifService.push(n);
            }

            // SMS in background (never blocks UI)
            if (wantSms) {
                trySendSmsAsync(phone, itemCount, firstItem, cartTotal);
            } else {
                MainApp.getInstance().showToast("Order placed successfully!", "success");
            }
        });

        task.setOnFailed(e -> orderFormError.setText(
                task.getException() != null ? task.getException().getMessage() : "Order failed."));

        Thread t = new Thread(task, "place-order");
        t.setDaemon(true);
        t.start();
    }

    /** SMS call is blocking HTTP — run it on a daemon thread. */
    private void trySendSmsAsync(String phone, int itemCount, String firstItem, double total) {
        String extra = itemCount > 1 ? " (+" + (itemCount - 1) + " more)" : "";
        String msg = "AgriCloud Order Confirmed!\n"
                + "Product: " + firstItem + extra + "\n"
                + "Total: $" + String.format("%.2f", total) + "\n"
                + "Status: Pending\n"
                + "Thank you for your order!";
        Thread t = new Thread(() -> {
            try {
                smsService.send(phone, msg);
                String toast = smsService.isDemo()
                        ? "Order saved \u2705 SMS sent (demo)"
                        : "Order placed & SMS sent to " + phone + "!";
                javafx.application.Platform.runLater(
                        () -> MainApp.getInstance().showToast(toast, "success"));
            } catch (Exception smsEx) {
                System.err.println("[SMS] Failed: " + smsEx.getMessage());
                javafx.application.Platform.runLater(
                        () -> MainApp.getInstance().showToast(
                                "Order saved, but SMS failed: " + smsEx.getMessage(), "error"));
            }
        }, "sms-send");
        t.setDaemon(true);
        t.start();
    }

    // =========================================================================
    // ── TAB 2: ORDERS DASHBOARD ───────────────────────────────────────────────
    // =========================================================================

    private void loadOrderPage() {
        User user = UserSession.getInstance().getCurrentUser();
        try {
            int total = user.getRole() == User.Role.ADMIN
                    ? orderService.countAllOrders()
                    : orderService.countFarmerOrders(user.getId());

            orderTotalPages = Math.max(1, (int) Math.ceil((double) total / ORDER_PAGE_SIZE));
            if (orderPage >= orderTotalPages) orderPage = orderTotalPages - 1;

            List<Order> orders = user.getRole() == User.Role.ADMIN
                    ? orderService.getAllOrdersPage(ORDER_PAGE_SIZE, orderPage * ORDER_PAGE_SIZE)
                    : orderService.getFarmerOrdersPage(user.getId(), ORDER_PAGE_SIZE, orderPage * ORDER_PAGE_SIZE);

            ordersTable.setItems(FXCollections.observableArrayList(orders));
            detailsTable.setItems(FXCollections.observableArrayList());

            orderPageLabel.setText("Page " + (orderPage + 1) + " / " + orderTotalPages);
            prevOrderBtn.setDisable(orderPage == 0);
            nextOrderBtn.setDisable(orderPage >= orderTotalPages - 1);

        } catch (Exception e) {
            MainApp.getInstance().showToast("Failed to load orders: " + e.getMessage(), "error");
        }
    }

    @FXML void onPrevOrderPage() { if (orderPage > 0) { orderPage--; loadOrderPage(); } }
    @FXML void onNextOrderPage() { if (orderPage < orderTotalPages - 1) { orderPage++; loadOrderPage(); } }

    // ── Orders table ──────────────────────────────────────────────────────────

    @SuppressWarnings("unchecked")
    private void setupOrdersTable() {
        TableColumn<Order, String> idCol = new TableColumn<>("ID");
        idCol.setCellValueFactory(p -> new SimpleStringProperty("#" + p.getValue().getId()));
        idCol.setPrefWidth(50);

        TableColumn<Order, String> productCol = new TableColumn<>("Product");
        productCol.setCellValueFactory(p -> new SimpleStringProperty(
                p.getValue().getProductName() != null ? p.getValue().getProductName() : "—"));
        productCol.setPrefWidth(140);

        TableColumn<Order, String> qtyCol = new TableColumn<>("Qty");
        qtyCol.setCellValueFactory(p -> new SimpleStringProperty(
                p.getValue().getQuantity() + " " + nvl(p.getValue().getProductUnit())));
        qtyCol.setPrefWidth(70);

        TableColumn<Order, String> dateCol = new TableColumn<>("Date");
        dateCol.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue().getFormattedDate()));
        dateCol.setPrefWidth(100);

        TableColumn<Order, String> totalCol = new TableColumn<>("Total");
        totalCol.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getTotalPrice())));
        totalCol.setPrefWidth(75);

        TableColumn<Order, String> statusCol = new TableColumn<>("Status");
        statusCol.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue().getStatus()));
        statusCol.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String s, boolean empty) {
                super.updateItem(s, empty);
                if (empty || s == null) { setGraphic(null); return; }
                Label badge = new Label(s);
                badge.getStyleClass().addAll("status-badge", "status-" + s);
                setGraphic(badge);
                setText(null);
            }
        });
        statusCol.setPrefWidth(90);

        TableColumn<Order, Order> actionsCol = new TableColumn<>("Actions");
        actionsCol.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        actionsCol.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(Order o, boolean empty) {
                super.updateItem(o, empty);
                if (empty || o == null) { setGraphic(null); return; }
                HBox box = new HBox(6);
                box.setAlignment(Pos.CENTER_LEFT);
                User user = UserSession.getInstance().getCurrentUser();

                Button edit = new Button("Edit");
                edit.getStyleClass().add("btn-secondary");
                edit.setOnAction(e -> openEditDrawer(o));
                box.getChildren().add(edit);

                if (user.getRole() == User.Role.ADMIN) {
                    Button delete = new Button("Delete");
                    delete.getStyleClass().add("btn-danger");
                    delete.setOnAction(e -> confirmDelete(o));
                    box.getChildren().add(delete);
                } else if (!("cancelled".equals(o.getStatus()) ||
                             "delivered".equals(o.getStatus()))) {
                    Button cancel = new Button("Cancel");
                    cancel.getStyleClass().add("btn-danger");
                    cancel.setOnAction(e -> confirmCancel(o));
                    box.getChildren().add(cancel);
                }
                setGraphic(box);
            }
        });
        actionsCol.setPrefWidth(160);
        actionsCol.setSortable(false);

        ordersTable.getColumns().addAll(idCol, productCol, qtyCol, dateCol, totalCol, statusCol, actionsCol);
        ordersTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY);
        ordersTable.setPlaceholder(new Label("No orders yet."));

        // Load detail rows when an order row is selected
        ordersTable.getSelectionModel().selectedItemProperty().addListener((obs, old, selected) -> {
            if (selected != null) loadDetailsFor(selected);
            else detailsTable.setItems(FXCollections.observableArrayList());
        });
    }

    // ── Detail rows ───────────────────────────────────────────────────────────

    private void loadDetailsFor(Order order) {
        try {
            List<OrderDetail> details = orderService.getOrderDetails(order.getId());
            detailsTable.setItems(FXCollections.observableArrayList(details));
        } catch (Exception e) {
            MainApp.getInstance().showToast("Could not load order details: " + e.getMessage(), "error");
        }
    }

    @SuppressWarnings("unchecked")
    private void setupDetailsTable() {
        TableColumn<OrderDetail, OrderDetail> imgCol = new TableColumn<>("");
        imgCol.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        imgCol.setCellFactory(col -> new TableCell<>() {
            private final ImageView iv = new ImageView();
            { iv.setFitWidth(38); iv.setFitHeight(38); iv.setPreserveRatio(true); }
            @Override protected void updateItem(OrderDetail d, boolean empty) {
                super.updateItem(d, empty);
                if (empty || d == null) { setGraphic(null); return; }
                String img = d.getProductImage();
                if (img != null && !img.isBlank()) {
                    File f = new File(img);
                    if (f.exists()) {
                        iv.setImage(new Image(f.toURI().toString(), 38, 38, true, true));
                        setGraphic(iv);
                        return;
                    }
                }
                Label lbl = new Label("—");
                lbl.setStyle("-fx-text-fill: #9E9E9E;");
                setGraphic(lbl);
            }
        });
        imgCol.setPrefWidth(50);
        imgCol.setSortable(false);

        TableColumn<OrderDetail, String> nameCol = new TableColumn<>("Product");
        nameCol.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue().getProductName()));
        nameCol.setPrefWidth(150);

        TableColumn<OrderDetail, String> qtyCol = new TableColumn<>("Qty");
        qtyCol.setCellValueFactory(p -> new SimpleStringProperty(
                p.getValue().getQuantity() + " " + nvl(p.getValue().getProductUnit())));
        qtyCol.setPrefWidth(70);

        TableColumn<OrderDetail, String> unitPriceCol = new TableColumn<>("Unit Price");
        unitPriceCol.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getUnitPrice())));
        unitPriceCol.setPrefWidth(80);

        TableColumn<OrderDetail, String> subtotalCol = new TableColumn<>("Subtotal");
        subtotalCol.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getSubtotal())));
        subtotalCol.setPrefWidth(80);

        detailsTable.getColumns().addAll(imgCol, nameCol, qtyCol, unitPriceCol, subtotalCol);
        detailsTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY);
        detailsTable.setFixedCellSize(48);
        detailsTable.setPlaceholder(new Label("Select an order above to see its items."));
    }

    // =========================================================================
    // ── EDIT ORDER DRAWER ────────────────────────────────────────────────────
    // =========================================================================

    private void openEditDrawer(Order order) {
        List<OrderDetail> existingDetails;
        try {
            existingDetails = orderService.getOrderDetails(order.getId());
        } catch (Exception e) {
            MainApp.getInstance().showToast("Failed to load order: " + e.getMessage(), "error");
            return;
        }

        // Cart items for the drawer
        ObservableList<CartItem> editCart = FXCollections.observableArrayList();
        for (OrderDetail d : existingDetails) {
            // Re-use CartItem with a minimal Product shell for display
            Product shell = new Product();
            shell.setId(d.getProductId());
            shell.setName(d.getProductName() != null ? d.getProductName() : "Unknown");
            shell.setImage(d.getProductImage());
            shell.setUnit(d.getProductUnit());
            shell.setPrice(d.getUnitPrice());
            shell.setQuantity(Integer.MAX_VALUE); // no stock cap in edit mode
            editCart.add(new CartItem(shell, d.getQuantity()));
        }

        drawerOverlay = new StackPane();
        drawerOverlay.setStyle("-fx-background-color: rgba(0,0,0,0.45);");
        drawerOverlay.setAlignment(Pos.CENTER_RIGHT);
        drawerOverlay.setOnMouseClicked(e -> {
            if (e.getTarget() == drawerOverlay) closeDrawer();
        });

        VBox panel = new VBox(12);
        panel.setMaxWidth(420);
        panel.setPrefWidth(420);
        panel.setStyle(
            "-fx-background-color: white;" +
            "-fx-background-radius: 8;" +
            "-fx-padding: 24;" +
            "-fx-effect: dropshadow(gaussian,rgba(0,0,0,0.35),24,0,-4,0);");

        Label title = new Label("Edit Order #" + order.getId());
        title.setStyle("-fx-font-size: 17px; -fx-font-weight: bold;");

        // Status
        ComboBox<String> statusCombo = new ComboBox<>();
        statusCombo.getItems().addAll(
                "pending","confirmed","processing","shipped","delivered","cancelled");
        statusCombo.setValue(order.getStatus());
        statusCombo.setMaxWidth(Double.MAX_VALUE);

        // Editable items table inside the drawer
        TableView<CartItem> editTable = new TableView<>(editCart);
        editTable.setPrefHeight(180);
        editTable.setFixedCellSize(46);

        TableColumn<CartItem, String> eName = new TableColumn<>("Product");
        eName.setCellValueFactory(p ->
                new SimpleStringProperty(p.getValue().getProduct().getName()));
        eName.setPrefWidth(130);

        TableColumn<CartItem, CartItem> eQty = buildEditQtyColumn(editCart, editTable);
        eQty.setPrefWidth(95);

        TableColumn<CartItem, String> eSub = new TableColumn<>("Subtotal");
        eSub.setCellValueFactory(p -> new SimpleStringProperty(
                String.format("$%.2f", p.getValue().getSubtotal())));
        eSub.setPrefWidth(75);

        TableColumn<CartItem, CartItem> eRm = new TableColumn<>("");
        eRm.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        eRm.setCellFactory(col -> new TableCell<>() {
            private final Button btn = new Button("✕");
            { btn.getStyleClass().add("btn-danger");
              btn.setStyle("-fx-min-width:26;-fx-min-height:22;-fx-padding:0 5;");
              btn.setOnAction(e -> { editCart.remove(getTableView().getItems().get(getIndex())); }); }
            @Override protected void updateItem(CartItem ci, boolean empty) {
                super.updateItem(ci, empty);
                setGraphic(empty || ci == null ? null : btn);
            }
        });
        eRm.setPrefWidth(40);
        eRm.setSortable(false);

        //noinspection unchecked
        editTable.getColumns().addAll(eName, eQty, eSub, eRm);
        editTable.setColumnResizePolicy(TableView.CONSTRAINED_RESIZE_POLICY);

        Label drawerError = new Label("");
        drawerError.setStyle("-fx-text-fill: #F44336; -fx-font-size: 11px;");
        drawerError.setWrapText(true);

        Button save   = new Button("Save Changes");
        Button cancel = new Button("Cancel");
        save.getStyleClass().add("btn-primary");
        cancel.getStyleClass().add("btn-secondary");
        save.setMaxWidth(Double.MAX_VALUE);
        cancel.setMaxWidth(Double.MAX_VALUE);
        cancel.setOnAction(e -> closeDrawer());

        save.setOnAction(e -> {
            drawerError.setText("");
            if (editCart.isEmpty()) {
                drawerError.setText("Order must have at least one item.");
                return;
            }
            // Build new details list from editCart
            List<OrderDetail> newDetails = new ArrayList<>();
            for (CartItem ci : editCart) {
                OrderDetail d = new OrderDetail();
                d.setProductId(ci.getProduct().getId());
                d.setQuantity(ci.getQuantity());
                newDetails.add(d);
            }
            order.setStatus(statusCombo.getValue());
            try {
                orderService.updateOrder(order, newDetails,
                        UserSession.getInstance().getCurrentUser());
                MainApp.getInstance().showToast("Order updated.", "success");
                closeDrawer();
                loadOrderPage();
                if (mainController != null) mainController.notifyProductsChanged();
            } catch (Exception ex) {
                drawerError.setText(ex.getMessage());
            }
        });

        panel.getChildren().addAll(
                title,
                labeled("Status", statusCombo),
                new Label("Items:"),
                editTable,
                drawerError,
                save, cancel);

        drawerOverlay.getChildren().add(panel);
        rootStack.getChildren().add(drawerOverlay);
    }

    /** Qty column with +/− buttons for the edit drawer table. */
    private TableColumn<CartItem, CartItem> buildEditQtyColumn(
            ObservableList<CartItem> cart, TableView<CartItem> table) {
        TableColumn<CartItem, CartItem> col = new TableColumn<>("Qty");
        col.setCellValueFactory(p -> new ReadOnlyObjectWrapper<>(p.getValue()));
        col.setCellFactory(c -> new TableCell<>() {
            private final Button minus = new Button("−");
            private final Label  num   = new Label();
            private final Button plus  = new Button("+");
            private final HBox   box   = new HBox(4, minus, num, plus);
            {
                box.setAlignment(Pos.CENTER);
                minus.setStyle("-fx-min-width:22;-fx-min-height:22;-fx-padding:0;");
                plus.setStyle ("-fx-min-width:22;-fx-min-height:22;-fx-padding:0;");
                minus.setOnAction(e -> {
                    CartItem ci = table.getItems().get(getIndex());
                    if (ci.getQuantity() > 1) ci.setQuantity(ci.getQuantity() - 1);
                    else cart.remove(ci);
                    table.refresh();
                });
                plus.setOnAction(e -> {
                    CartItem ci = table.getItems().get(getIndex());
                    ci.setQuantity(ci.getQuantity() + 1);
                    table.refresh();
                });
            }
            @Override protected void updateItem(CartItem ci, boolean empty) {
                super.updateItem(ci, empty);
                if (empty || ci == null) { setGraphic(null); return; }
                num.setText(String.valueOf(ci.getQuantity()));
                setGraphic(box);
            }
        });
        col.setSortable(false);
        return col;
    }

    private void closeDrawer() {
        rootStack.getChildren().remove(drawerOverlay);
        drawerOverlay = null;
    }

    // =========================================================================
    // ── PDF EXPORT ───────────────────────────────────────────────────────────
    // =========================================================================

    @FXML
    void onExportPdf() {
        Order o = ordersTable.getSelectionModel().getSelectedItem();
        if (o == null) {
            MainApp.getInstance().showToast("Select an order first.", "error");
            return;
        }

        FileChooser chooser = new FileChooser();
        chooser.setTitle("Save Order PDF");
        chooser.setInitialFileName("Order_" + o.getId() + ".pdf");
        chooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("PDF Files", "*.pdf"));
        Stage stage = (Stage) rootStack.getScene().getWindow();
        File file = chooser.showSaveDialog(stage);
        if (file == null) return;

        try (PDDocument doc = new PDDocument()) {
            PDPage page = new PDPage(PDRectangle.A4);
            doc.addPage(page);

            try (PDPageContentStream cs = new PDPageContentStream(doc, page)) {
                float m  = 50;
                float pw = page.getMediaBox().getWidth();
                float y  = page.getMediaBox().getHeight() - m;

                // ── Header bar ───────────────────────────────────────────────
                cs.setNonStrokingColor(0.18f, 0.49f, 0.20f); // green
                cs.addRect(m, y - 6, pw - 2 * m, 34);
                cs.fill();
                cs.setNonStrokingColor(0, 0, 0);

                cs.beginText();
                cs.setFont(PDType1Font.HELVETICA_BOLD, 18);
                cs.setNonStrokingColor(1f, 1f, 1f);
                cs.newLineAtOffset(m + 8, y + 4);
                cs.showText("AgriCloud  –  Order Receipt");
                cs.endText();
                cs.setNonStrokingColor(0f, 0f, 0f);
                y -= 50;

                // ── Order meta ───────────────────────────────────────────────
                String delivDateStr = o.getDeliveryDate() != null
                        ? o.getDeliveryDate().format(DATE_FMT) : "—";
                String notesStr = o.getNotes() != null && !o.getNotes().isBlank()
                        ? o.getNotes() : "—";
                if (notesStr.length() > 60) notesStr = notesStr.substring(0, 57) + "...";
                String[][] meta = {
                    {"Order #",        String.valueOf(o.getId())},
                    {"Date",           o.getFormattedDate()},
                    {"Status",         pdfSafe(o.getStatus())},
                    {"Customer ID",    String.valueOf(o.getCustomerId())},
                    {"Delivery Date",  pdfSafe(delivDateStr)},
                    {"Notes",          pdfSafe(notesStr)},
                };
                for (String[] row : meta) {
                    cs.beginText();
                    cs.setFont(PDType1Font.HELVETICA_BOLD, 10);
                    cs.newLineAtOffset(m, y);
                    cs.showText(row[0] + ":");
                    cs.endText();
                    cs.beginText();
                    cs.setFont(PDType1Font.HELVETICA, 10);
                    cs.newLineAtOffset(m + 100, y);
                    cs.showText(row[1]);
                    cs.endText();
                    y -= 15;
                }
                y -= 10;

                // ── Divider ──────────────────────────────────────────────────
                cs.setLineWidth(0.8f);
                cs.moveTo(m, y); cs.lineTo(pw - m, y); cs.stroke();
                y -= 18;

                // ── Product details table ─────────────────────────────────────
                float c1 = m, c2 = m + 200, c3 = m + 290, c4 = m + 370, c5 = m + 450;

                // Table header
                cs.setNonStrokingColor(0.93f, 0.93f, 0.93f);
                cs.addRect(m, y - 4, pw - 2 * m, 18);
                cs.fill();
                cs.setNonStrokingColor(0f, 0f, 0f);

                for (String[] hdr : new String[][]{
                        {String.valueOf(c1),"Product"},
                        {String.valueOf(c2),"Category"},
                        {String.valueOf(c3),"Qty"},
                        {String.valueOf(c4),"Unit Price"},
                        {String.valueOf(c5),"Total"}}) {
                    cs.beginText();
                    cs.setFont(PDType1Font.HELVETICA_BOLD, 9);
                    cs.newLineAtOffset(Float.parseFloat(hdr[0]) + 2, y);
                    cs.showText(hdr[1]);
                    cs.endText();
                }
                y -= 20;

                // Single product row (one order = one product)
                String name = pdfSafe(o.getProductName() != null ? o.getProductName() : "Unknown");
                if (name.length() > 28) name = name.substring(0, 25) + "...";
                String cat  = pdfSafe(o.getProductCategory() != null ? o.getProductCategory() : "—");
                String unit = pdfSafe(nvl(o.getProductUnit()));

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 10);
                cs.newLineAtOffset(c1 + 2, y); cs.showText(name); cs.endText();

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 10);
                cs.newLineAtOffset(c2 + 2, y); cs.showText(cat); cs.endText();

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 10);
                cs.newLineAtOffset(c3 + 2, y);
                cs.showText(o.getQuantity() + (unit.isBlank() ? "" : " " + unit));
                cs.endText();

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 10);
                cs.newLineAtOffset(c4 + 2, y);
                cs.showText(String.format("$%.2f", o.getUnitPrice()));
                cs.endText();

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 10);
                cs.newLineAtOffset(c5 + 2, y);
                cs.showText(String.format("$%.2f", o.getTotalPrice()));
                cs.endText();
                y -= 24;

                // ── Total row ────────────────────────────────────────────────
                cs.setLineWidth(0.5f);
                cs.moveTo(m, y); cs.lineTo(pw - m, y); cs.stroke();
                y -= 16;

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA_BOLD, 11);
                cs.newLineAtOffset(c4, y); cs.showText("TOTAL"); cs.endText();

                cs.beginText(); cs.setFont(PDType1Font.HELVETICA_BOLD, 11);
                cs.newLineAtOffset(c5, y);
                cs.showText(String.format("$%.2f", o.getTotalPrice()));
                cs.endText();
                y -= 30;

                // ── Status badge ─────────────────────────────────────────────
                String status = nvl(o.getStatus()).toUpperCase();
                cs.beginText(); cs.setFont(PDType1Font.HELVETICA_BOLD, 9);
                cs.newLineAtOffset(m, y);
                cs.showText("Order Status: " + status);
                cs.endText();
                y -= 30;

                // ── Footer ───────────────────────────────────────────────────
                cs.setLineWidth(0.5f);
                cs.moveTo(m, y); cs.lineTo(pw - m, y); cs.stroke();
                y -= 14;
                cs.beginText(); cs.setFont(PDType1Font.HELVETICA, 8);
                cs.newLineAtOffset(m, y);
                cs.showText("Generated by AgriCloud Farm Management System");
                cs.endText();
            }

            doc.save(file);
            MainApp.getInstance().showToast("PDF saved: " + file.getName(), "success");

        } catch (Exception e) {
            MainApp.getInstance().showToast("PDF export failed: " + e.getMessage(), "error");
            e.printStackTrace();
        }
    }

    /** Sanitise a string for PDType1Font (WinAnsiEncoding: chars 32-255). */
    private String pdfSafe(String text) {
        if (text == null) return "";
        StringBuilder sb = new StringBuilder();
        for (char c : text.toCharArray()) {
            sb.append((c >= 32 && c <= 255) ? c : '?');
        }
        return sb.toString();
    }

    // =========================================================================
    // Cancel / Delete helpers
    // =========================================================================

    private void confirmDelete(Order o) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle("Delete Order");
        alert.setHeaderText("Permanently delete Order #" + o.getId() + "?");
        alert.setContentText("Stock will be restored if the order was still active.");
        alert.showAndWait().ifPresent(btn -> {
            if (btn == ButtonType.OK) {
                try {
                    orderService.deleteOrder(o.getId(),
                            UserSession.getInstance().getCurrentUser());
                    MainApp.getInstance().showToast("Order deleted.", "info");
                    loadOrderPage();
                    if (mainController != null) mainController.notifyProductsChanged();
                } catch (Exception e) {
                    MainApp.getInstance().showToast(e.getMessage(), "error");
                }
            }
        });
    }

    private void confirmCancel(Order o) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle("Cancel Order");
        alert.setHeaderText("Cancel Order #" + o.getId() + "?");
        alert.setContentText("Stock will be restored.");
        alert.showAndWait().ifPresent(btn -> {
            if (btn == ButtonType.OK) {
                try {
                    orderService.cancelOrder(o.getId(),
                            UserSession.getInstance().getCurrentUser());
                    MainApp.getInstance().showToast("Order cancelled.", "info");
                    loadOrderPage();
                    if (mainController != null) mainController.notifyProductsChanged();
                } catch (Exception e) {
                    MainApp.getInstance().showToast(e.getMessage(), "error");
                }
            }
        });
    }

    // =========================================================================
    // UI helpers
    // =========================================================================

    private Node labeled(String text, Node control) {
        VBox box = new VBox(4);
        Label lbl = new Label(text);
        lbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #757575;");
        box.getChildren().addAll(lbl, control);
        return box;
    }

    private String nvl(String s) { return s != null ? s : ""; }

    // =========================================================================
    // CartItem – inner model class
    // =========================================================================

    /** Holds a Product + quantity for the cart and edit-drawer tables. */
    public static class CartItem {
        private final Product product;
        private int quantity;

        public CartItem(Product product, int quantity) {
            this.product  = product;
            this.quantity = quantity;
        }

        public Product getProduct()        { return product; }
        public int     getQuantity()       { return quantity; }
        public void    setQuantity(int q)  { this.quantity = q; }
        public double  getSubtotal()       { return product.getPrice() * quantity; }
    }
}
