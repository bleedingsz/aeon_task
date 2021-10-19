<?php

class Notification {

    // TEST

    /**
     * get all notifications
     * @param bool $viewed
     * @return array
     */
    public static function getNotifications($viewed = false): array{
        var_dump($viewed);
        if(!Session::$user_id) response(error_response(1002, 'Application authorization failed: method is unavailable with service token.'));
        $WHERE = "";
        $temp =
            [
                ':user_id' => Session::$user_id
            ];
        if($viewed){
            $WHERE .= " and viewed= :viewed";
            $temp[':viewed'] = 1;
        }
        $WHERE = $viewed ? " and viewed= :viewed" : "";
        $query = "SELECT title, description, viewed, created FROM user_notifications WHERE user_id= :user_id $WHERE";

        $prepare = DB::connect()->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $prepare->execute($temp) or die (DB::error());
        return $prepare->fetchAll();
    }
}
