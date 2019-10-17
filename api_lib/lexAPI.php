<?php
require "query_generator.php";
require "config.php";
/**
 * lexAPI
 * 
 * @package 
 * @author alexanderdth
 * @author lexsytems.ml
 * @copyright 2019
 * @version v1.0
 * @access public
 */

class lexAPI
{
    //access sql generator function

    public function build_query($params, $table_name)
    {
        return gen_query($params, $table_name);
    }

    public function insert_data($table, $cols, $vals)
    {
        return PushData($table, $cols, $vals);
    }

    //clean any string input
    public function clean($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-\|]/', '', $string); // Removes special chars.
    }
    //athenticate or verify api keys validity

    public function authenticate($api_key)
    {
        global $con;

        $get_api = mysqli_query($con,
            "SELECT api_key,requests FROM api_keys WHERE api_key='$api_key' LIMIT 1");

        if (mysqli_num_rows($get_api) == 1) {
            //update api status and count use
            $a = mysqli_fetch_assoc($get_api);

            $count = $a["requests"] + 1;

            $update = mysqli_query($con, "UPDATE api_keys SET last_used='" . date("Y-m-d H:i:s") .
                "',requests='$count' WHERE api_key='$api_key' LIMIT 1");

            if ($update) {
                return array("status" => "true");
            } else {
                return array("status" => "false", "error" => "invalid api key!");
            }
        } else {
            return array("status" => "false", "error" => "invalid api key!");
        }
    }

    public function return_users($limit = 30, $page = 1, $api_key)
    {

        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            //proceed to extracting all users based on paging

            //restrict 100 records per page
            if (empty($limit)) {
                $limit = "30";
            } elseif ($limit > "100") {
                $limit = "100";
            } else {
                $limit = $limit;
            }

            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $offset = ($page - 1) * $limit;
            $user_query = "SELECT * FROM users order by registered DESC ";
            $all_users = mysqli_num_rows(mysqli_query($con, $user_query));
            $total_pages = ceil($all_users / $limit);

            $get_users = mysqli_query($con, "$user_query limit $offset,$limit");

            if (mysqli_num_rows($get_users) > 0) {
                while ($users = mysqli_fetch_assoc($get_users)) {
                    $user_list[] = $users;
                }

                return array(
                    "status" => "true",
                    "users" => $user_list,
                    "page" => $page,
                    "limit" => $limit,
                    "total_pages" => $total_pages,
                    "total_users" => $all_users,
                    );
            } else {
                return array("status" => "false", "error" => "User DB seems to be empty.");
            }
        } else {
            return $check;
        }

    }
    //return user
    public function return_user($api_key, $user_id)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            $get_user = mysqli_query($con, "SELECT * FROM users WHERE id='$user_id' LIMIT 1");
            if (mysqli_num_rows($get_user) == 1) {
                $user = mysqli_fetch_assoc($get_user);

                return array("status" => "true", "user_info" => $user);
            } else {
                return array("status" => "false", "error" => "No user found.");
            }
        } else {
            return $check;
        }
    }
    //find users
    public function find_user($api_key, $page = 1, $keyword)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            //FULLINDEX KEYWORD SEPARATION
            $keyword = rawurldecode($keyword);
            $keywords = explode(" ", $keyword);
            $keywords = array_filter($keywords);
            foreach ($keywords as $k) {
                $keyword_[] = $this->clean($k);
            }


            $keys = implode(",", $keyword_);

            //REGEXP KEYWORDS
            $regexp_input = $keyword;

            $regexp_input = explode(" ", $regexp_input);
            foreach ($regexp_input as $rg) {
                $exp[] = $this->clean($rg);
            }

            $regexp_input = $exp;

            $regexp_input = array_filter($regexp_input);

            if (count($regexp_input) > 1) {
                $regexp_input = implode("|", $regexp_input);

                //$regexp_input = clean($regexp_input);
                $regexp_input = rtrim($regexp_input, "|");

            } else {
                $regexp_input = $regexp_input[0];
            }
            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $limit = 100;

            $offset = ($page - 1) * $limit;

            $reg_keys = $regexp_input;

            $search_query = "SELECT *,
                MATCH(firstName) 
                AGAINST('$keys' IN BOOLEAN MODE)
                AS score FROM users
                WHERE MATCH(firstName) AGAINST ('$keys' IN BOOLEAN MODE)
                OR firstName REGEXP '$reg_keys' 
                ORDER by score DESC
                ";

            $find_user = mysqli_query($con, "$search_query LIMIT $offset,$limit");
            $result_count = mysqli_num_rows(mysqli_query($con, $search_query));
            $total_pages = ceil($result_count / $limit);

            if (mysqli_num_rows($find_user) > 0) {
                while ($results = mysqli_fetch_assoc($find_user)) {
                    $user_list[] = $results;
                }

                return array(
                    "status" => "true",
                    "results" => $user_list,
                    "page" => $page,
                    "total_results" => $result_count,
                    "total_pages" => $total_pages);

            } else {
                return array("status" => "false", "error" => "Nothing found for your query!");
            }


        } else {
            return $check;
        }
    }
    //return vehicles
    public function return_vehicles($limit = 30, $page = 1, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            if (empty($limit)) {
                $limit = "30";
            } elseif ($limit > "100") {
                $limit = "100";
            } else {
                $limit = $limit;
            }
            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $offset = ($page - 1) * $limit;

            $vehicle_query = "SELECT * FROM vehicles ";
            $count_vehicles = mysqli_num_rows(mysqli_query($con, $vehicle_query));
            $total_pages = ceil($count_vehicles / $limit);

            $grab_vehicles = mysqli_query($con, "$vehicle_query LIMIT $offset,$limit");

            if (mysqli_num_rows($grab_vehicles) > 0) {
                while ($vehicles = mysqli_fetch_assoc($grab_vehicles)) {
                    $vehicle_data[] = $vehicles;
                }

                return array(
                    "status" => "true",
                    "vehicles" => $vehicle_data,
                    "page" => $page,
                    "limit" => $limit,
                    "total_vehicles" => $count_vehicles,
                    "total_pages" => $total_pages);
            } else {
                return array("status" => "false", "error" =>
                        "Vehicle database seems to be empty!");
            }

        } else {
            return $check;
        }


    }
    //return vehicle
    public function return_vehicle($api_key, $vehicle_id)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            $get_vehicle = mysqli_query($con, "SELECT * FROM vehicles WHERE id='$vehicle_id' LIMIT 1");
            if (mysqli_num_rows($get_vehicle) == 1) {
                $vehicle = mysqli_fetch_assoc($get_vehicle);

                return array("status" => "true", "vehicle_info" => $vehicle);
            } else {
                return array("status" => "false", "error" => "No vehicle found.");
            }
        } else {
            return $check;
        }
    }
    //return orders

    public function return_orders($limit = 30, $page = 1, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            if (empty($limit)) {
                $limit = "30";
            } elseif ($limit > "100") {
                $limit = "100";
            } else {
                $limit = $limit;
            }
            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $offset = ($page - 1) * $limit;

            $order_query = "SELECT * FROM orders ";
            $count_orders = mysqli_num_rows(mysqli_query($con, $order_query));
            $total_pages = ceil($count_orders / $limit);

            $grab_orders = mysqli_query($con, "$order_query LIMIT $offset,$limit");

            if (mysqli_num_rows($grab_orders) > 0) {
                while ($orders = mysqli_fetch_assoc($grab_orders)) {
                    $order_data[] = $orders;
                }

                return array(
                    "status" => "true",
                    "orders" => $order_data,
                    "page" => $page,
                    "limit" => $limit,
                    "total_orders" => $count_orders,
                    "total_pages" => $total_pages);
            } else {
                return array("status" => "false", "error" => "order database seems to be empty!");
            }

        } else {
            return $check;
        }
    }

    //return order info

    public function return_order($api_key, $order_id)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            $get_order = mysqli_query($con, "SELECT * FROM orders WHERE id='$order_id' LIMIT 1");
            if (mysqli_num_rows($get_order) == 1) {
                $order = mysqli_fetch_assoc($get_order);

                return array("status" => "true", "order_info" => $order);
            } else {
                return array("status" => "false", "error" => "No order found.");
            }
        } else {
            return $check;
        }
    }

    //find vehicles

    public function find_vehicle($api_key, $page = 1, $keyword)
    {

        global $con;


        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            //FULLINDEX KEYWORD SEPARATION
            $keyword = rawurldecode($keyword);
            $keywords = explode(" ", $keyword);
            $keywords = array_filter($keywords);
            foreach ($keywords as $k) {
                $keyword_[] = $this->clean($k);
            }


            $keys = implode(",", $keyword_);

            //REGEXP KEYWORDS
            $regexp_input = $keyword;

            $regexp_input = explode(" ", $regexp_input);
            foreach ($regexp_input as $rg) {
                $exp[] = $this->clean($rg);
            }

            $regexp_input = $exp;

            $regexp_input = array_filter($regexp_input);

            if (count($regexp_input) > 1) {
                $regexp_input = implode("|", $regexp_input);

                //$regexp_input = clean($regexp_input);
                $regexp_input = rtrim($regexp_input, "|");

            } else {
                $regexp_input = $regexp_input[0];
            }
            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $limit = 100;

            $offset = ($page - 1) * $limit;

            $reg_keys = $regexp_input;

            $search_query = "SELECT *,
                MATCH(firstName) 
                AGAINST('$keys' IN BOOLEAN MODE)
                AS score FROM vehicles
                WHERE MATCH(firstName) AGAINST ('$keys' IN BOOLEAN MODE)
                OR firstName REGEXP '$reg_keys' 
                ORDER by score DESC
                ";

            $find_vehicle = mysqli_query($con, "$search_query LIMIT $offset,$limit");
            $result_count = mysqli_num_rows(mysqli_query($con, $search_query));
            $total_pages = ceil($result_count / $limit);

            if (mysqli_num_rows($find_vehicle) > 0) {
                while ($results = mysqli_fetch_assoc($find_vehicle)) {
                    $vehicle_list[] = $results;
                }

                return array(
                    "status" => "true",
                    "results" => $vehicle_list,
                    "page" => $page,
                    "total_results" => $result_count,
                    "total_pages" => $total_pages);

            } else {
                return array("status" => "false", "error" => "Nothing found for your query!");
            }


        } else {
            return $check;
        }

    }
    //return services

    public function return_services($limit = 30, $page = 1, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            if (empty($limit)) {
                $limit = "30";
            } elseif ($limit > "100") {
                $limit = "100";
            } else {
                $limit = $limit;
            }
            if (empty($page)) {
                $page = 1;
            } elseif ($page < 1) {

                $page = 1;
            } else {

                $page = $page;
            }

            $offset = ($page - 1) * $limit;

            $service_query = "SELECT * FROM services ";
            $count_services = mysqli_num_rows(mysqli_query($con, $service_query));
            $total_pages = ceil($count_services / $limit);

            $grab_services = mysqli_query($con, "$service_query LIMIT $offset,$limit");

            if (mysqli_num_rows($grab_services) > 0) {
                while ($services = mysqli_fetch_assoc($grab_services)) {
                    $service_data[] = $services;
                }

                return array(
                    "status" => "true",
                    "services" => $service_data,
                    "page" => $page,
                    "limit" => $limit,
                    "total_services" => $count_services,
                    "total_pages" => $total_pages);
            } else {
                return array("status" => "false", "error" =>
                        "service database seems to be empty!");
            }

        } else {
            return $check;
        }


    }

    //return service info

    public function return_service($service_id, $api_key)
    {
        global $con;
        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            $get_service = mysqli_query($con, "SELECT * FROM services WHERE id='$service_id' LIMIT 1");
            if (mysqli_num_rows($get_service) == 1) {
                $service = mysqli_fetch_assoc($get_service);

                return array("status" => "true", "service_info" => $service);
            } else {
                return array("status" => "false", "error" => "No service found.");
            }
        } else {
            return $check;
        }
    }

    //return user_types

    public function return_user_types($api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {
            $get_user_type = mysqli_query($con, "SELECT * FROM user_types");
            if (mysqli_num_rows($get_user_type) > 0) {
                while ($user_type = mysqli_fetch_assoc($get_user_type)) {
                    $user_types[] = $user_type;
                }

                return array("status" => "true", "user_types" => $user_types);
            } else {
                return array("status" => "false", "error" => "No user_type found.");
            }
        } else {
            return $check;
        }
    }

    public function insert_user($values, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            if (!empty($values) && is_array($values)) {
                $get_user_fields = mysqli_query($con,
                    "SHOW COLUMNS FROM users WHERE Field <> 'id'");

                if ($get_user_fields) {
                    while ($fields = mysqli_fetch_assoc($get_user_fields)) {
                        $field_names[] = $fields["Field"];
                    }

                    $columns = $field_names;
                
                    $insert_data = $this->insert_data("users", $columns, $values);

                    if ($insert_data["result"] == "true") {
                        return array(
                            "status" => "true",
                            "user_id" => $insert_data["query_id"],
                            "fields_affected" => $columns);
                    } else {
                        return array("status" => "false", "error" => $insert_data["error"]);
                    }
                } else {
                    return array("status" => "false", "error" => mysqli_error($con));
                }
            } 
            else {
                return array("status" => "false", "error" =>
                        "user values are empty  or not specified!");
            }

        } else {
            return $check;
        }

    }
    //insert vehicle inside the database

    public function insert_vehicle($values, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            if (!empty($values) && is_array($values)) {
                $get_vehicle_fields = mysqli_query($con,
                    "SHOW COLUMNS FROM vehicles WHERE Field <> 'id'");

                if ($get_vehicle_fields) {
                    while ($fields = mysqli_fetch_assoc($get_vehicle_fields)) {
                        $field_names[] = $fields["Field"];
                    }

                    $columns = $field_names;

                    $insert_data = $this->insert_data("vehicles", $columns, $values);

                    if ($insert_data["result"] == "true") {
                        return array(
                            "status" => "true",
                            "vehicle_id" => $insert_data["query_id"],
                            "fields_affected" => $columns);
                    } else {
                        return array("status" => "false", "error" => $insert_data["error"]);
                    }
                } else {
                    return array("status" => "false", "error" => mysqli_error($con));
                }
            } else {
                return array("status" => "false", "error" =>
                        "vehicle values are empty  or not specified!");
            }

        } else {
            return $check;
        }
    }
    //insert services
    public function insert_service($values, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            if (!empty($values) && is_array($values)) {
                $get_service_fields = mysqli_query($con,
                    "SHOW COLUMNS FROM services WHERE Field <> 'id'");

                if ($get_service_fields) {
                    while ($fields = mysqli_fetch_assoc($get_service_fields)) {
                        $field_names[] = $fields["Field"];
                    }

                    $columns = $field_names;

                    $insert_data = $this->insert_data("services", $columns, $values);

                    if ($insert_data["result"] == "true") {
                        return array(
                            "status" => "true",
                            "service_id" => $insert_data["query_id"],
                            "fields_affected" => $columns);
                    } else {
                        return array("status" => "false", "error" => $insert_data["error"]);
                    }
                } else {
                    return array("status" => "false", "error" => mysqli_error($con));
                }
            } else {
                return array("status" => "false", "error" =>
                        "service values are empty  or not specified!");
            }

        } else {
            return $check;
        }
    }
    //insert order data
    public function insert_order($values, $api_key)
    {
        global $con;

        $check = $this->authenticate($api_key);

        if ($check["status"] == "true") {

            if (!empty($values) && is_array($values)) {
                $get_order_fields = mysqli_query($con,
                    "SHOW COLUMNS FROM orders WHERE Field <> 'id'");

                if ($get_order_fields) {
                    while ($fields = mysqli_fetch_assoc($get_order_fields)) {
                        $field_names[] = $fields["Field"];
                    }

                    $columns = $field_names;

                    $insert_data = $this->insert_data("orders", $columns, $values);

                    if ($insert_data["result"] == "true") {
                        return array(
                            "status" => "true",
                            "order_id" => $insert_data["query_id"],
                            "fields_affected" => $columns);
                    } else {
                        return array("status" => "false", "error" => $insert_data["error"]);
                    }
                } else {
                    return array("status" => "false", "error" => mysqli_error($con));
                }
            } else {
                return array("status" => "false", "error" =>
                        "order values are empty  or not specified!");
            }

        } else {
            return $check;
        }

    }
}

?>