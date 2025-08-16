<?php
// Auto-generated stub file
if (!defined('ABSPATH')) exit;


class WSP_API {
    public static function register_routes() {
        register_rest_route("wsp/v1", "/extract", [
            "methods" => "POST",
            "callback" => [__CLASS__, "handle_extraction"],
            "permission_callback" => [__CLASS__, "verify_api_key"]
        ]);
        
        register_rest_route("wsp/v1", "/send", [
            "methods" => "POST",
            "callback" => [__CLASS__, "send_message"],
            "permission_callback" => [__CLASS__, "verify_api_key"]
        ]);
    }
    
    public static function verify_api_key($request) {
        $api_key = $request->get_header("X-API-Key");
        if (empty($api_key)) {
            return new WP_Error("missing_api_key", "API Key mancante", ["status" => 401]);
        }
        
        // Per test accetta questa chiave
        if ($api_key === "test-api-key-123") {
            $request->set_param("_customer_id", 1);
            return true;
        }
        
        global $wpdb;
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wsp_customers WHERE api_key = %s AND status = %s",
            $api_key, "active"
        ));
        
        if (!$customer) {
            return new WP_Error("invalid_api_key", "API Key non valida", ["status" => 401]);
        }
        
        $request->set_param("_customer_id", $customer->id);
        return true;
    }
    
    public static function handle_extraction($request) {
        $customer_id = $request->get_param("_customer_id");
        $data = $request->get_json_params();
        
        $results = [
            "success" => true,
            "processed" => 0,
            "imported" => 0
        ];
        
        // Logica base per salvare numeri
        global $wpdb;
        $numbers = isset($data["numbers"]) ? $data["numbers"] : [$data];
        
        foreach ($numbers as $number_data) {
            $results["processed"]++;
            
            $sender_number = preg_replace("/\D+/", "", $number_data["senderNumber"] ?? "");
            if (empty($sender_number)) continue;
            
            $wpdb->insert(
                $wpdb->prefix . "wsp_extracted_numbers",
                [
                    "customer_id" => $customer_id,
                    "sender_number" => $sender_number,
                    "sender_name" => $number_data["senderName"] ?? "",
                    "campaign_date" => date("Y-m-d")
                ],
                ["%d", "%s", "%s", "%s"]
            );
            
            if ($wpdb->insert_id) {
                $results["imported"]++;
            }
        }
        
        return new WP_REST_Response($results, 200);
    }
    
    public static function send_message($request) {
        $customer_id = $request->get_param("_customer_id");
        $data = $request->get_json_params();
        
        // Implementazione base
        return new WP_REST_Response([
            "success" => true,
            "message_id" => uniqid()
        ], 200);
    }
}