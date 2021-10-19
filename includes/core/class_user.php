<?php

class User {

    // GENERAL

    public static function user_info($data) {
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, first_name, last_name, middle_name, email, gender_id, count_notifications FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'],
                'gender_id' => (int) $row['gender_id'],
                'email' => $row['email'],
                'phone' => (int) $row['phone'],
                'phone_str' => phone_formatting($row['phone']),
                'count_notifications' => (int) $row['count_notifications']
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => '',
                'middle_name' => '',
                'gender_id' => 0,
                'email' => '',
                'phone' => '',
                'phone_str' => '',
                'count_notifications' => 0
            ];
        }
    }

    public static function user_get_or_create($phone) {
        // validate
        $user = User::user_info(['phone' => $phone]);
        $user_id = $user['id'];
        // create
        if (!$user_id) {
            DB::query("INSERT INTO users (status_access, phone, created) VALUES ('3', '".$phone."', '".Session::$ts."');") or die (DB::error());
            $user_id = DB::insert_id();
        }
        // output
        return $user_id;
    }

    // TEST

    public static function owner_info():string {
        if(!Session::$user_id) response(error_response(1002, 'Application authorization failed: method is unavailable with service token.'));
        $query = "UPDATE user_notifications SET viewed= :viewed WHERE user_id= :user_id and viewed= :not_viewed" or die (DB::error()) ;
        $temp =
            [
                ':viewed' => 1,
                ':user_id' => Session::$user_id,
                ':not_viewed' => 0
            ];
        $prepare = DB::connect()->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $prepare->execute($temp) or die (DB::error());
        return "ok";
    }

    public static function owner_update($data = []):string {

        if(!$data) return error_response(1003, 'The array is empty.');
        //vars
        $first_name = isset($data['first_name']) && !empty($data['first_name']) ? $data['first_name'] : 0;
        $last_name = isset($data['last_name']) && !empty($data['last_name']) ? $data['last_name'] : 0;
        $phone = isset($data['phone']) && !empty($data['last_name']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;
        $middle_name = isset($data['middle_name']) ? $data['middle_name'] : 0;
        $email = isset($data['email']) ? strtolower($data['email']) : 0;

        //validate
        if (!$first_name) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['first_name' => 'empty field']);
        if (!$last_name) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['last_name' => 'empty field']);
        if (!$phone) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['phone' => 'empty field']);
        if (iconv_strlen($phone) != 11) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['phone' => '
invalid number of characters']);
        if ((int) substr($phone, 0 , 1 ) !== 7 ) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['phone' => 'does not start with 7']);
        if (!$middle_name) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['middle_name' => 'empty field']);
        if (!$email) return error_response(1003, 'One of the parameters was missing or was passed in the wrong format.', ['email' => 'empty field']);

        //update db
        $query = "UPDATE users SET first_name= :first_name, last_name= :last_name,middle_name= :middle_name, email= :email, phone= :phone WHERE user_id= :user_id LIMIT 1;";
        $query .= "INSERT INTO user_notifications (user_id, title, description, created) VALUES (:user_id, :title, :description, :created)";

        $temp =
            [
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':middle_name' => $middle_name,
            ':email' => $email,
            ':phone' => $phone,
            ':user_id' => Session::$user_id,
            ':title' => "You have successfully changed the data",
            ':description' => "Some data was changed",
            ':created' => Session::$ts
        ];
        $prepare = DB::connect()->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $prepare->execute($temp) or die (DB::error());
        return "ok";
    }

}
