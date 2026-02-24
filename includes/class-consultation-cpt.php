<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Consultation Custom Post Type
 * 
 * Used for Consultation Types, Pricing, Duration, and Extra options.
 */
class Consultation_CPT
{

    /**
     * Register the Custom Post Type.
     */
    public static function register()
    {
        $labels = [
            'name' => _x('Consultation Types', 'Post Type General Name', 'booking-app'),
            'singular_name' => _x('Consultation Type', 'Post Type Singular Name', 'booking-app'),
            'menu_name' => __('Consultations', 'booking-app'),
            'name_admin_bar' => __('Consultation Type', 'booking-app'),
            'archives' => __('Consultation Archives', 'booking-app'),
            'attributes' => __('Consultation Attributes', 'booking-app'),
            'parent_item_colon' => __('Parent Consultation:', 'booking-app'),
            'all_items' => __('All Consultation Types', 'booking-app'),
            'add_new_item' => __('Add New Consultation Type', 'booking-app'),
            'add_new' => __('Add New', 'booking-app'),
            'new_item' => __('New Consultation Type', 'booking-app'),
            'edit_item' => __('Edit Consultation Type', 'booking-app'),
            'update_item' => __('Update Consultation Type', 'booking-app'),
            'view_item' => __('View Consultation Type', 'booking-app'),
            'view_items' => __('View Consultation Types', 'booking-app'),
            'search_items' => __('Search Consultation Type', 'booking-app'),
            'not_found' => __('Not found', 'booking-app'),
            'not_found_in_trash' => __('Not found in Trash', 'booking-app'),
            'featured_image' => __('Featured Image', 'booking-app'),
            'set_featured_image' => __('Set featured image', 'booking-app'),
            'remove_featured_image' => __('Remove featured image', 'booking-app'),
            'use_featured_image' => __('Use as featured image', 'booking-app'),
            'insert_into_item' => __('Insert into consultation', 'booking-app'),
            'uploaded_to_this_item' => __('Uploaded to this consultation', 'booking-app'),
            'items_list' => __('Consultations list', 'booking-app'),
            'items_list_navigation' => __('Consultations list navigation', 'booking-app'),
            'filter_items_list' => __('Filter consultations list', 'booking-app'),
        ];

        $args = [
            'label' => __('Consultation Type', 'booking-app'),
            'description' => __('Consultation Types for Booking App', 'booking-app'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'booking-app', // Show as submenu of Bookings
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'page',
            'show_in_rest' => true,
        ];

        register_post_type('consultation', $args);
    }
}
