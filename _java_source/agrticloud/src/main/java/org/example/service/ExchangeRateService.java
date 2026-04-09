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
 * Fetches USD → TND exchange rate from open.er-api.com.
 * Caches the rate for 12 hours; falls back to the last known rate if the
 * API is unreachable.  No API key required.
 *
 * Usage:
 *   ExchangeRateService fx = ExchangeRateService.getInstance();
 *   fx.refreshIfNeeded(rate -> updateUI(rate));   // async, non-blocking
 *   double tnd = fx.toTnd(14.99);                 // synchronous, uses cached value
 */
public class ExchangeRateService {

    private static final ExchangeRateService INSTANCE = new ExchangeRateService();

    private static final String API_URL =
            "https://open.er-api.com/v6/latest/USD";

    // Regex to extract   "TND": <number>   from the JSON response
    private static final Pattern TND_PATTERN =
            Pattern.compile("\"TND\"\\s*:\\s*([0-9]+(?:\\.[0-9]+)?)");

    private static final Duration CACHE_TTL = Duration.ofHours(12);

    // Approximate USD→TND fallback (updated Jan 2025 ≈ 3.09)
    private double  cachedRate  = 3.09;
    private Instant cacheTime   = Instant.EPOCH;
    private boolean stale       = false;  // true if last API call failed

    private final HttpClient http = HttpClient.newHttpClient();

    private ExchangeRateService() {}

    public static ExchangeRateService getInstance() { return INSTANCE; }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /** @return the cached USD→TND rate (never blocks). */
    public double getRate()        { return cachedRate; }

    /** @return true when the rate comes from cache because the last fetch failed. */
    public boolean isStale()       { return stale; }

    /** Converts a USD amount to TND using the cached rate. */
    public double toTnd(double usd) { return usd * cachedRate; }

    /**
     * Formats a USD amount as a TND string, e.g. "41.355 TND".
     * Shows 3 decimal places (standard for TND).
     */
    public String formatTnd(double usd) {
        return String.format("%.3f TND", toTnd(usd));
    }

    /**
     * If the cache is older than CACHE_TTL, fires an async refresh in the
     * background (won't block the caller).  The optional {@code onComplete}
     * callback is invoked on the JavaFX Application Thread once the rate is
     * updated (or has failed).
     */
    public void refreshIfNeeded(Consumer<Double> onComplete) {
        if (Duration.between(cacheTime, Instant.now()).compareTo(CACHE_TTL) < 0) {
            // Cache is fresh — nothing to do
            if (onComplete != null) Platform.runLater(() -> onComplete.accept(cachedRate));
            return;
        }
        Task<Double> task = new Task<>() {
            @Override
            protected Double call() throws Exception {
                HttpRequest req = HttpRequest.newBuilder()
                        .uri(URI.create(API_URL))
                        .timeout(Duration.ofSeconds(8))
                        .GET()
                        .build();
                HttpResponse<String> resp =
                        http.send(req, HttpResponse.BodyHandlers.ofString());
                if (resp.statusCode() != 200)
                    throw new Exception("HTTP " + resp.statusCode());
                Matcher m = TND_PATTERN.matcher(resp.body());
                if (!m.find())
                    throw new Exception("TND not found in response");
                return Double.parseDouble(m.group(1));
            }
        };
        task.setOnSucceeded(e -> {
            cachedRate = task.getValue();
            cacheTime  = Instant.now();
            stale      = false;
            System.out.printf("[FX] USD→TND rate updated: %.4f%n", cachedRate);
            if (onComplete != null) onComplete.accept(cachedRate);
        });
        task.setOnFailed(e -> {
            stale = true;
            System.err.println("[FX] Rate fetch failed, using cached " + cachedRate
                    + ": " + task.getException().getMessage());
            if (onComplete != null) Platform.runLater(() -> onComplete.accept(cachedRate));
        });
        Thread t = new Thread(task, "fx-rate-fetch");
        t.setDaemon(true);
        t.start();
    }

    /** Convenience — fire refresh with no callback. */
    public void refreshIfNeeded() { refreshIfNeeded(null); }
}
