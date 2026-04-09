package org.example.model;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;

/**
 * Represents an in-app notification.
 * Persisted notifications (ORDER_STATUS) are stored in the DB.
 * Dynamic notifications (LOW_STOCK, PAYMENT_DUE) are generated on-the-fly
 * and are never inserted into the DB; their id is 0.
 */
public class Notification {

    public enum Type { ORDER_STATUS, LOW_STOCK, PAYMENT_DUE }

    private long          id;
    private Type          type;
    private String        title;
    private String        message;
    private long          relatedId;    // order id or product id
    private String        relatedType;  // "ORDER" | "PRODUCT"
    private boolean       read;
    private LocalDateTime createdAt;

    public Notification() {}

    public Notification(Type type, String title, String message,
                        long relatedId, String relatedType) {
        this.type        = type;
        this.title       = title;
        this.message     = message;
        this.relatedId   = relatedId;
        this.relatedType = relatedType;
        this.read        = false;
        this.createdAt   = LocalDateTime.now();
    }

    // ── Getters / Setters ───────────────────────────────────────────────────

    public long          getId()                      { return id; }
    public void          setId(long id)               { this.id = id; }

    public Type          getType()                    { return type; }
    public void          setType(Type t)              { this.type = t; }

    public String        getTitle()                   { return title; }
    public void          setTitle(String t)           { this.title = t; }

    public String        getMessage()                 { return message; }
    public void          setMessage(String m)         { this.message = m; }

    public long          getRelatedId()               { return relatedId; }
    public void          setRelatedId(long id)        { this.relatedId = id; }

    public String        getRelatedType()             { return relatedType; }
    public void          setRelatedType(String rt)    { this.relatedType = rt; }

    public boolean       isRead()                     { return read; }
    public void          setRead(boolean r)           { this.read = r; }

    public LocalDateTime getCreatedAt()               { return createdAt; }
    public void          setCreatedAt(LocalDateTime t){ this.createdAt = t; }

    /** Whether this notification lives in the DB (id > 0) or is dynamic. */
    public boolean isPersisted() { return id > 0; }

    /** Human-readable "time ago" string, e.g. "2m ago", "3h ago", "yesterday". */
    public String getTimeAgo() {
        if (createdAt == null) return "";
        LocalDateTime now = LocalDateTime.now();
        long mins  = ChronoUnit.MINUTES.between(createdAt, now);
        long hours = ChronoUnit.HOURS.between(createdAt, now);
        long days  = ChronoUnit.DAYS.between(createdAt, now);
        if (mins  < 1)  return "just now";
        if (mins  < 60) return mins  + "m ago";
        if (hours < 24) return hours + "h ago";
        if (days  == 1) return "yesterday";
        if (days  < 7)  return days  + "d ago";
        return createdAt.format(DateTimeFormatter.ofPattern("MMM dd"));
    }

    /** Emoji icon representing the notification type. */
    public String getIcon() {
        return switch (type) {
            case ORDER_STATUS -> "📦";
            case LOW_STOCK    -> "⚠";
            case PAYMENT_DUE  -> "💳";
        };
    }
}
