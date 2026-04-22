package org.example.service;

import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.util.Base64;

/**
 * Sends SMS notifications via Twilio REST API.
 *
 * Modes (controlled by the SMS_MODE environment variable):
 *
 *   SMS_MODE=mock    (default) – no network call; prints to console.
 *   SMS_MODE=twilio            – real Twilio call; requires all three env vars below.
 *
 * Required env vars for Twilio mode:
 *   TWILIO_ACCOUNT_SID   – your Account SID  (starts with "AC…")
 *   TWILIO_AUTH_TOKEN    – your Auth Token
 *   TWILIO_FROM_NUMBER   – a Twilio-owned sender number in E.164 format (e.g. "+12025551234")
 *
 * The TO number (recipient) is supplied at call time by the user.
 * Never hardcode credentials here — use environment variables only.
 */
public class SmsService {

    private static final String MODE        = System.getenv().getOrDefault("SMS_MODE", "mock");
    private static final String ACCOUNT_SID = System.getenv("TWILIO_ACCOUNT_SID");
    private static final String AUTH_TOKEN  = System.getenv("TWILIO_AUTH_TOKEN");
    private static final String FROM_NUMBER = System.getenv("TWILIO_FROM_NUMBER");

    /** True only when SMS_MODE=twilio AND all three env vars are set. */
    private static final boolean USE_TWILIO =
            "twilio".equalsIgnoreCase(MODE)
            && ACCOUNT_SID  != null && !ACCOUNT_SID.isBlank()
            && AUTH_TOKEN   != null && !AUTH_TOKEN.isBlank()
            && FROM_NUMBER  != null && !FROM_NUMBER.isBlank();

    static {
        if (USE_TWILIO) {
            System.out.println("[SMS] Mode: TWILIO (from " + FROM_NUMBER + ")");
        } else {
            System.out.println("[SMS] Mode: MOCK (set SMS_MODE=twilio + env vars to enable real sending)");
        }
    }

    private final HttpClient http = HttpClient.newHttpClient();

    /** @return true when running in demo/mock mode – no real SMS is sent. */
    public boolean isDemo() {
        return !USE_TWILIO;
    }

    /**
     * Sends (or simulates) an SMS to the given phone number.
     *
     * @param toPhone  Recipient number in E.164 format, e.g. "+21655123456"
     * @param message  Text body (keep under 160 chars for a single segment)
     * @throws Exception if Twilio returns a non-2xx response
     */
    public void send(String toPhone, String message) throws Exception {
        if (toPhone == null || toPhone.isBlank())
            throw new IllegalArgumentException("Phone number is required.");

        if (!USE_TWILIO) {
            System.out.println("[SMS] MOCK – would send to " + toPhone + ": " + message);
            return;
        }

        String endpoint = "https://api.twilio.com/2010-04-01/Accounts/"
                + ACCOUNT_SID + "/Messages.json";

        String body = "From=" + encode(FROM_NUMBER)
                + "&To="   + encode(toPhone)
                + "&Body=" + encode(message);

        String credentials = Base64.getEncoder().encodeToString(
                (ACCOUNT_SID + ":" + AUTH_TOKEN).getBytes(StandardCharsets.UTF_8));

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(endpoint))
                .header("Authorization", "Basic " + credentials)
                .header("Content-Type", "application/x-www-form-urlencoded")
                .POST(HttpRequest.BodyPublishers.ofString(body))
                .build();

        HttpResponse<String> response =
                http.send(request, HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() < 200 || response.statusCode() >= 300) {
            throw new Exception("Twilio error " + response.statusCode()
                    + ": " + response.body());
        }

        System.out.println("[SMS] Sent to " + toPhone + " (status " + response.statusCode() + ")");
    }

    private static String encode(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8);
    }
}
