package org.example.service;

import org.example.dao.AnalyticsDAO;

import java.sql.SQLException;
import java.util.List;

/**
 * Thin service wrapper over AnalyticsDAO.
 * Controllers call this; DAOs are never touched directly from a controller.
 */
public class AnalyticsService {

    private final AnalyticsDAO dao = new AnalyticsDAO();

    public List<Object[]> getRevenuePerDay(int days)   throws SQLException { return dao.getRevenuePerDay(days); }
    public List<Object[]> getBestSellers(int limit)    throws SQLException { return dao.getBestSellingProducts(limit); }
    public List<Object[]> getStockByCategory()         throws SQLException { return dao.getStockValueByCategory(); }

    public int    getOrdersThisMonth()   throws SQLException { return dao.getOrdersThisMonth(); }
    public double getRevenueThisMonth()  throws SQLException { return dao.getRevenueThisMonth(); }
    public double getAvgOrderValue()     throws SQLException { return dao.getAvgOrderValue(); }
    public double getStockTotalValue()   throws SQLException { return dao.getStockTotalValue(); }
}
