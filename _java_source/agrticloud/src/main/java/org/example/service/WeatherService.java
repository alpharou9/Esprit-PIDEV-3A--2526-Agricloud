package org.example.service;

import javafx.application.Platform;
import javafx.concurrent.Task;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.time.Instant;
import java.util.function.Consumer;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Fetches current weather from Open-Meteo (free, no API key required).
 * Defaults to Tunis, Tunisia (lat=36.8065, lon=10.1815).
 * Caches the result for 30 minutes; falls back silently on failure.
 *
 * Usage:
 *   WeatherService.getInstance().refreshIfNeeded(text -> weatherLabel.setText(text));
 */
public class WeatherService {

    private static final WeatherService INSTANCE = new WeatherService();
    public static WeatherService getInstance() { return INSTANCE; }

    private static final double DEFAULT_LAT  = 36.8065;
    private static final double DEFAULT_LON  = 10.1815;
    private static final String DEFAULT_CITY = "Tunis";
    private static final Duration CACHE_TTL  = Duration.ofMinutes(30);

    // Parse Open-Meteo "current" object fields
    private static final Pattern TEMP_PATTERN =
            Pattern.compile("\"temperature_2m\"\\s*:\\s*([-\\d.]+)");
    private static final Pattern CODE_PATTERN =
            Pattern.compile("\"weather_code\"\\s*:\\s*(\\d+)");

    private String  cachedText = "";        // formatted display string, e.g. "🌤 22°C · Tunis"
    private Instant cacheTime  = Instant.EPOCH;

    private final HttpClient http = HttpClient.newHttpClient();

    private WeatherService() {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * If the cache is fresh (<30 min old) calls {@code onReady} immediately on the FX thread.
     * Otherwise fires an async fetch and calls {@code onReady} on the FX thread when done.
     * Never blocks the caller.
     */
    public void refreshIfNeeded(Consumer<String> onReady) {
        if (!cachedText.isEmpty() &&
                Duration.between(cacheTime, Instant.now()).compareTo(CACHE_TTL) < 0) {
            if (onReady != null) Platform.runLater(() -> onReady.accept(cachedText));
            return;
        }

        String url = "https://api.open-meteo.com/v1/forecast"
                + "?latitude="  + DEFAULT_LAT
                + "&longitude=" + DEFAULT_LON
                + "&current=temperature_2m,weather_code"
                + "&timezone=auto";

        Task<String> task = new Task<>() {
            @Override
            protected String call() throws Exception {
                HttpRequest req = HttpRequest.newBuilder()
                        .uri(URI.create(url))
                        .timeout(Duration.ofSeconds(8))
                        .GET()
                        .build();
                HttpResponse<String> resp =
                        http.send(req, HttpResponse.BodyHandlers.ofString());
                if (resp.statusCode() != 200)
                    throw new Exception("HTTP " + resp.statusCode());

                String body = resp.body();
                Matcher tm = TEMP_PATTERN.matcher(body);
                Matcher cm = CODE_PATTERN.matcher(body);
                if (!tm.find() || !cm.find())
                    throw new Exception("Unexpected JSON structure from Open-Meteo");

                double temp = Double.parseDouble(tm.group(1));
                int    code = Integer.parseInt(cm.group(1));
                return wmoEmoji(code) + " " + Math.round(temp) + "\u00b0C \u00b7 " + DEFAULT_CITY;
            }
        };

        task.setOnSucceeded(e -> {
            cachedText = task.getValue();
            cacheTime  = Instant.now();
            System.out.println("[Weather] " + cachedText);
            if (onReady != null) onReady.accept(cachedText);
        });
        task.setOnFailed(e -> {
            System.err.println("[Weather] Fetch failed: " + task.getException().getMessage());
            // Keep stale cache if available; otherwise show nothing
            if (!cachedText.isEmpty() && onReady != null)
                Platform.runLater(() -> onReady.accept(cachedText));
        });

        Thread t = new Thread(task, "weather-fetch");
        t.setDaemon(true);
        t.start();
    }

    // =========================================================================
    // WMO Weather Interpretation Codes → emoji
    // =========================================================================

    private static String wmoEmoji(int code) {
        if (code == 0)         return "\u2600";   // ☀ Clear sky
        if (code <= 2)         return "\uD83C\uDF24"; // 🌤 Mainly/partly clear
        if (code == 3)         return "\u2601";   // ☁ Overcast
        if (code <= 48)        return "\uD83C\uDF2B"; // 🌫 Fog (45, 48)
        if (code <= 57)        return "\uD83C\uDF26"; // 🌦 Drizzle (51–57)
        if (code <= 67)        return "\uD83C\uDF27"; // 🌧 Rain (61–67)
        if (code <= 77)        return "\u2744";   // ❄ Snow (71–77)
        if (code <= 82)        return "\uD83C\uDF26"; // 🌦 Showers (80–82)
        if (code <= 86)        return "\uD83C\uDF28"; // 🌨 Snow showers (85–86)
        return "\u26C8";                           // ⛈ Thunderstorm (95–99)
    }
}
