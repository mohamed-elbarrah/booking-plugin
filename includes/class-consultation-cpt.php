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
            'name' => _x('Consultation Types', 'Post Type General Name', 'mbs-booking'),
            'singular_name' => _x('Consultation Type', 'Post Type Singular Name', 'mbs-booking'),
            'menu_name' => __('Consultations', 'mbs-booking'),
            'name_admin_bar' => __('Consultation Type', 'mbs-booking'),
            'archives' => __('Consultation Archives', 'mbs-booking'),
            'attributes' => __('Consultation Attributes', 'mbs-booking'),
            'parent_item_colon' => __('Parent Consultation:', 'mbs-booking'),
            'all_items' => __('All Consultation Types', 'mbs-booking'),
            'add_new_item' => __('Add New Consultation Type', 'mbs-booking'),
            'add_new' => __('Add New', 'mbs-booking'),
            'new_item' => __('New Consultation Type', 'mbs-booking'),
            'edit_item' => __('Edit Consultation Type', 'mbs-booking'),
            'update_item' => __('Update Consultation Type', 'mbs-booking'),
            'view_item' => __('View Consultation Type', 'mbs-booking'),
            'view_items' => __('View Consultation Types', 'mbs-booking'),
            'search_items' => __('Search Consultation Type', 'mbs-booking'),
            'not_found' => __('Not found', 'mbs-booking'),
            'not_found_in_trash' => __('Not found in Trash', 'mbs-booking'),
            'featured_image' => __('Featured Image', 'mbs-booking'),
            'set_featured_image' => __('Set featured image', 'mbs-booking'),
            'remove_featured_image' => __('Remove featured image', 'mbs-booking'),
            'use_featured_image' => __('Use as featured image', 'mbs-booking'),
            'insert_into_item' => __('Insert into consultation', 'mbs-booking'),
            'uploaded_to_this_item' => __('Uploaded to this consultation', 'mbs-booking'),
            'items_list' => __('Consultations list', 'mbs-booking'),
            'items_list_navigation' => __('Consultations list navigation', 'mbs-booking'),
            'filter_items_list' => __('Filter consultations list', 'mbs-booking'),
        ];

        $args = [
            'label' => __('Consultation Type', 'mbs-booking'),
            'description' => __('Consultation Types for Booking App', 'mbs-booking'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'mbs-booking', // Show as submenu of Bookings
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
