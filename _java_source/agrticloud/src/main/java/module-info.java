module agrticloud {
    requires javafx.controls;
    requires javafx.fxml;
    requires javafx.graphics;
    requires java.sql;
    requires java.net.http;
    requires org.apache.pdfbox;

    opens org.example to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
    opens org.example.model to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
    opens org.example.controller to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
    opens org.example.session to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
    opens org.example.service to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
    opens org.example.dao to javafx.graphics, javafx.fxml, javafx.base, javafx.controls;
}
