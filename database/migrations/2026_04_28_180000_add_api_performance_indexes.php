<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addIndex('bc_services', ['status', 'object_model'], 'idx_bc_services_status_model');

        $this->addIndex('bc_hotels', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_hotels_status_loc_featured_id');
        $this->addIndex('bc_hotels', ['status', 'price'], 'idx_bc_hotels_status_price');
        $this->addIndex('bc_hotels', ['status', 'review_score'], 'idx_bc_hotels_status_review');

        $this->addIndex('bc_tours', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_tours_status_loc_featured_id');
        $this->addIndex('bc_tours', ['status', 'price'], 'idx_bc_tours_status_price');
        $this->addIndex('bc_tours', ['status', 'review_score'], 'idx_bc_tours_status_review');

        $this->addIndex('bc_cars', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_cars_status_loc_featured_id');
        $this->addIndex('bc_cars', ['status', 'price'], 'idx_bc_cars_status_price');
        $this->addIndex('bc_cars', ['status', 'review_score'], 'idx_bc_cars_status_review');

        $this->addIndex('bc_spaces', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_spaces_status_loc_featured_id');
        $this->addIndex('bc_spaces', ['status', 'price'], 'idx_bc_spaces_status_price');
        $this->addIndex('bc_spaces', ['status', 'review_score'], 'idx_bc_spaces_status_review');

        $this->addIndex('bc_boats', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_boats_status_loc_featured_id');
        $this->addIndex('bc_boats', ['status', 'price'], 'idx_bc_boats_status_price');
        $this->addIndex('bc_boats', ['status', 'review_score'], 'idx_bc_boats_status_review');

        $this->addIndex('bc_flight', ['status', 'is_featured', 'id'], 'idx_bc_flight_status_featured_id');
        $this->addIndex('bc_flight', ['status', 'price'], 'idx_bc_flight_status_price');
        $this->addIndex('bc_flight', ['status', 'review_score'], 'idx_bc_flight_status_review');

        $this->addIndex('bc_properties', ['status', 'location_id', 'is_featured', 'id'], 'idx_bc_properties_status_loc_featured_id');
        $this->addIndex('bc_properties', ['status', 'price'], 'idx_bc_properties_status_price');
        $this->addIndex('bc_properties', ['status', 'review_score'], 'idx_bc_properties_status_review');

        $this->addIndex('bc_buses', ['is_active', 'status', 'bus_type', 'id'], 'idx_bc_buses_active_status_type_id');
        $this->addIndex('bc_buses', ['is_active', 'status', 'departure_city', 'arrival_city'], 'idx_bc_buses_active_status_route');

        $this->addIndex('bc_tour_term', ['term_id', 'target_id'], 'idx_bc_tour_term_term_target');
        $this->addIndex('bc_tour_term', ['target_id', 'term_id'], 'idx_bc_tour_term_target_term');
        $this->addIndex('bc_hotel_term', ['term_id', 'target_id'], 'idx_bc_hotel_term_term_target');
        $this->addIndex('bc_hotel_term', ['target_id', 'term_id'], 'idx_bc_hotel_term_target_term');
        $this->addIndex('bc_car_term', ['term_id', 'target_id'], 'idx_bc_car_term_term_target');
        $this->addIndex('bc_car_term', ['target_id', 'term_id'], 'idx_bc_car_term_target_term');
        $this->addIndex('bc_space_term', ['term_id', 'target_id'], 'idx_bc_space_term_term_target');
        $this->addIndex('bc_space_term', ['target_id', 'term_id'], 'idx_bc_space_term_target_term');
        $this->addIndex('bc_boat_term', ['term_id', 'target_id'], 'idx_bc_boat_term_term_target');
        $this->addIndex('bc_boat_term', ['target_id', 'term_id'], 'idx_bc_boat_term_target_term');
        $this->addIndex('bc_flight_term', ['term_id', 'target_id'], 'idx_bc_flight_term_term_target');
        $this->addIndex('bc_flight_term', ['target_id', 'term_id'], 'idx_bc_flight_term_target_term');
        $this->addIndex('bc_property_term', ['term_id', 'target_id'], 'idx_bc_property_term_term_target');
        $this->addIndex('bc_property_term', ['target_id', 'term_id'], 'idx_bc_property_term_target_term');
    }

    public function down(): void
    {
        $this->dropIndex('bc_services', 'idx_bc_services_status_model');

        $this->dropIndex('bc_hotels', 'idx_bc_hotels_status_loc_featured_id');
        $this->dropIndex('bc_hotels', 'idx_bc_hotels_status_price');
        $this->dropIndex('bc_hotels', 'idx_bc_hotels_status_review');

        $this->dropIndex('bc_tours', 'idx_bc_tours_status_loc_featured_id');
        $this->dropIndex('bc_tours', 'idx_bc_tours_status_price');
        $this->dropIndex('bc_tours', 'idx_bc_tours_status_review');

        $this->dropIndex('bc_cars', 'idx_bc_cars_status_loc_featured_id');
        $this->dropIndex('bc_cars', 'idx_bc_cars_status_price');
        $this->dropIndex('bc_cars', 'idx_bc_cars_status_review');

        $this->dropIndex('bc_spaces', 'idx_bc_spaces_status_loc_featured_id');
        $this->dropIndex('bc_spaces', 'idx_bc_spaces_status_price');
        $this->dropIndex('bc_spaces', 'idx_bc_spaces_status_review');

        $this->dropIndex('bc_boats', 'idx_bc_boats_status_loc_featured_id');
        $this->dropIndex('bc_boats', 'idx_bc_boats_status_price');
        $this->dropIndex('bc_boats', 'idx_bc_boats_status_review');

        $this->dropIndex('bc_flight', 'idx_bc_flight_status_featured_id');
        $this->dropIndex('bc_flight', 'idx_bc_flight_status_price');
        $this->dropIndex('bc_flight', 'idx_bc_flight_status_review');

        $this->dropIndex('bc_properties', 'idx_bc_properties_status_loc_featured_id');
        $this->dropIndex('bc_properties', 'idx_bc_properties_status_price');
        $this->dropIndex('bc_properties', 'idx_bc_properties_status_review');

        $this->dropIndex('bc_buses', 'idx_bc_buses_active_status_type_id');
        $this->dropIndex('bc_buses', 'idx_bc_buses_active_status_route');

        $this->dropIndex('bc_tour_term', 'idx_bc_tour_term_term_target');
        $this->dropIndex('bc_tour_term', 'idx_bc_tour_term_target_term');
        $this->dropIndex('bc_hotel_term', 'idx_bc_hotel_term_term_target');
        $this->dropIndex('bc_hotel_term', 'idx_bc_hotel_term_target_term');
        $this->dropIndex('bc_car_term', 'idx_bc_car_term_term_target');
        $this->dropIndex('bc_car_term', 'idx_bc_car_term_target_term');
        $this->dropIndex('bc_space_term', 'idx_bc_space_term_term_target');
        $this->dropIndex('bc_space_term', 'idx_bc_space_term_target_term');
        $this->dropIndex('bc_boat_term', 'idx_bc_boat_term_term_target');
        $this->dropIndex('bc_boat_term', 'idx_bc_boat_term_target_term');
        $this->dropIndex('bc_flight_term', 'idx_bc_flight_term_term_target');
        $this->dropIndex('bc_flight_term', 'idx_bc_flight_term_target_term');
        $this->dropIndex('bc_property_term', 'idx_bc_property_term_term_target');
        $this->dropIndex('bc_property_term', 'idx_bc_property_term_target_term');
    }

    private function addIndex(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable) {
            // Ignore duplicate/unsupported index errors for safe rolling deploys.
        }
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable) {
            // Ignore when index does not exist.
        }
    }
};

