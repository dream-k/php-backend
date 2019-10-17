<?php
//generate dynamic query php 
//author lexsystems 
//mothewrfucking hack3r in the friggin house


//generates advanced sort query

/**
 * EXAMPLE OF ARRAY PARAMS
 *  $params = array
 * (
 *    "sort" => 
 *    array
 *    (
 *         1 => array
 *         (
 *             "GET" => "name_asc",
 *             "sql_field" => "last_name",
 *             "order_sql" => "ASC", 
 *         ),
 *         2 => array
 *         (
 *             "GET" => "name_desc",
 *             "sql_field" => "last_name",
 *             "order_sql" => "DESC", 
 *         ),
 *         3 => array
 *         (
 *             "GET" => "date_desc",
 *             "sql_field" => "created_at",
 *             "order_sql" => "DESC", 
 *         ),
 *         4 => array
 *         (
 *             "GET" => "date_asc",
 *             "sql_field" => "created_at",
 *             "order_sql" => "ASC", 
 *         ),
 *    ),
 *    
 *     "criteria" =>
 *     array
 *     (
 *          1 => array
 *         (
 *             "GET" => "rank",
 *             "sql_field" => "user_rank",
 *            
 *         ),
 *         2 => array
 *         (
 *             "GET" => "status",
 *             "sql_field" => "status",
 *             
 *         ),
 *     ),
 *     
 *     
 * );
 */

function gen_query($params,$table_name)
{
       
       //defining sort order
       if(isset($params["sort"]))
       {
        
       
       foreach($params["sort"] as $sort)
       {
            $get_field = $sort["GET"];
            $sql_field = $sort["sql_field"];
            $sql_order = $sort["order_sql"];
            
            if(isset($_GET["sort"]))
            {
            if($_GET["sort"] == $get_field)
            {
                $sql_sort = "ORDER by $sql_field $sql_order";
                
            }
            }
            else
            {
                $sql_sort = "";
            }
            
       }
       }
       else
       {
        $sql_sort = null;
       }
       
       //define criteria order as in get criteria from something
       if(isset($params["criteria"]))
       {
        
       
       foreach($params["criteria"] as $c)
       {
        $c_field = $c["GET"];
        $c_sql = $c["sql_field"];
           
           if(array_key_exists($c_field, $_GET))
           {
            $criteria_input = $_GET["$c_field"];
            if($criteria_input !="")
            {
            $criteria[] = "$c_sql = '$criteria_input'";
            }
           }
          
       }
        }
        else
        {
            $criteria[] = null;
        }
        
        //define regexp criteria
        if(isset($params["regexp"]))
        { 
       foreach($params["regexp"] as $r)
       {
            $r_field = $r["GET"];
            $r_sql = $r["sql_field"];
            
           if(array_key_exists($r_field, $_GET))
           {
            $regexp_input = $_GET["$r_field"];
           
            if($regexp_input !="")
            {
               
                $regexp_input = explode(" ",$regexp_input);
                foreach($regexp_input as $rg)
                {
                    $exp[] = clean($rg);
                }
                
                $regexp_input = $exp;
                
                $regexp_input  = array_filter($regexp_input);
              
                if(count($regexp_input) > 1)
                {
                    $regexp_input = implode("|",$regexp_input);
                    echo $regexp_input;
                    //$regexp_input = clean($regexp_input);
                    $regexp_input = rtrim($regexp_input,"|");
                   
                }
                else
                {
                    $regexp_input = $regexp_input[0];
                }
               
              
               
                
            $regexp[] = "$r_sql REGEXP '$regexp_input'";
            }
           }
      }
       }
       else
       {
        $regexp[] = null;
       }
       
       //define between params
       if(isset($params["between"]))
       {
            foreach($params["between"] as $b)
            {
                
                $b_from = $b["GET"];
                $b_to = $b["GET2"];
                $b_sql = $b["sql_field"];
                
                $from = $_GET["$b_from"];
                $to = $_GET["$b_from"];
                
                if(array_key_exists($b_from,$_GET) && array_key_exists($b_to,$_GET))
                {
                    $between[] = "$b_sql BETWEEN '$from' AND '$to'";
                }
            }
            
          
       }
       else
       {
        $between[] = null;
       }
                                             
        //check for empty criteria array
      if(!is_null($criteria))
      {
        $criteria = implode(' AND ', $criteria);
      }
      else
      {
        $criteria = null;
      }
      
      //check for empty regexp array
      if(!is_null($regexp))
      {
         $regexp = implode (' OR ', $regexp);
      }
      else
      {
        $regexp = null;
      }
      
      //check for empty between array
      if(!is_null($between))
      {
         $between = implode(' AND ',$between);
      }
      else
      {
        $between  = null;
      }

        // return data
      $data = array($criteria,$regexp,$between);
      $data = array_filter($data);
      $data = implode(" AND ",$data);
      
      if(!empty($data))
      {
        $sql = "SELECT * FROM $table_name WHERE $data $sql_sort";
      }
      else
      {
        $sql = "SELECT * FROM $table_name ";
      }
      
     
     return $sql;
      
      
}

//insert sql data
//make sure you create array of columns
// make sure you create array of values 
//both arrays must be the same in size and lenght
 function PushData($table, $columns, $values)
{
    //declare con global(conection params))
    global $con;
    
  $column_count = count($columns);
  $overwriteArr = array_fill(0, $column_count, '?');

  for ($i = 0; $i < sizeof($columns); $i++)
  {
    $columns[$i] = trim($columns[$i]);
    $columns[$i] = '`' . $columns[$i] . '`';
  }

  $query = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $overwriteArr) . ")";

  foreach ($values as $value)
  {
    $value = trim($value);
    $value = mysqli_real_escape_string($con, $value);
    $value = '"' . $value . '"';
    $query = preg_replace('/\?/', $value, $query, 1);
  }
  $result = mysqli_query($con, $query);
  
  if($result == true)
  {
        return array("result" => "true","query_id" => mysqli_insert_id($con));
  }
  else
  {
        return array("result" => "false","error" => mysqli_error($con));
  }
}


function check_rows($table, $column, $value)
{
    global $con;
    
    $num_rows = mysqli_query($con, "SELECT $column FROM $table WHERE $column='".addslashes($value)."'");
    $num = mysqli_num_rows($num_rows);
    
    return $num;
}
?>