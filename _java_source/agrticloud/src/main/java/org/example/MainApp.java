package org.example;

import javafx.animation.FadeTransition;
import javafx.application.Application;
import javafx.beans.property.BooleanProperty;
import javafx.beans.property.SimpleBooleanProperty;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Label;
import javafx.scene.layout.StackPane;
import javafx.stage.Stage;
import javafx.util.Duration;

import java.util.Objects;

public class MainApp extends Application {

    // -------------------------------------------------------------------------
    // Singleton access so controllers can call showToast / isDarkMode
    // -------------------------------------------------------------------------
    private static MainApp instance;

    public static MainApp getInstance() { return instance; }

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    private StackPane toastLayer;
    private Scene scene;
    private final BooleanProperty darkMode = new SimpleBooleanProperty(false);

    // -------------------------------------------------------------------------
    // JavaFX entry point
    // -------------------------------------------------------------------------
    @Override
    public void start(Stage stage) throws Exception {
        instance = this;

        FXMLLoader loader = new FXMLLoader(
                getClass().getResource("/org/example/main-view.fxml"));
        Parent root = loader.load();

        scene = new Scene(root, 1050, 650);
        scene.getStylesheets().add(
                Objects.requireNonNull(
                        getClass().getResource("/style.css")).toExternalForm());

        // Apply / remove the theme-dark CSS class whenever dark mode changes
        darkMode.addListener((obs, o, n) -> {
            if (n) scene.getRoot().getStyleClass().add("theme-dark");
            else   scene.getRoot().getStyleClass().remove("theme-dark");
        });

        stage.setTitle("AgriCloud – Farm Management");
        stage.setScene(scene);
        stage.setMinWidth(900);
        stage.setMinHeight(600);
        stage.show();
    }

    // -------------------------------------------------------------------------
    // Toast notifications
    // -------------------------------------------------------------------------
    public void setToastLayer(StackPane layer) {
        this.toastLayer = layer;
    }

    public void showToast(String message, String type) {
        if (toastLayer == null) return;

        Label toast = new Label(message);
        toast.getStyleClass().addAll("toast", "toast-" + type);
        toastLayer.getChildren().add(toast);

        FadeTransition fadeIn = new FadeTransition(Duration.millis(200), toast);
        fadeIn.setFromValue(0);
        fadeIn.setToValue(1);

        FadeTransition fadeOut = new FadeTransition(Duration.millis(400), toast);
        fadeOut.setFromValue(1);
        fadeOut.setToValue(0);
        fadeOut.setDelay(Duration.seconds(2.8));
        fadeOut.setOnFinished(e -> toastLayer.getChildren().remove(toast));

        fadeIn.play();
        fadeOut.play();

    }

    // -------------------------------------------------------------------------
    // Theme
    // -------------------------------------------------------------------------
    public BooleanProperty darkModeProperty() { return darkMode; }
    public boolean isDarkMode()               { return darkMode.get(); }
    public void setDarkMode(boolean on)       { darkMode.set(on); }

    // -------------------------------------------------------------------------
    // Launch
    // -------------------------------------------------------------------------
    public static void main(String[] args) {
        launch(args);
    }
}
