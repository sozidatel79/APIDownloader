<?php

class Utils {
    public static function secondsToDaysHoursMinutes($seconds): string {
        $days = floor($seconds/86400);
        $hours = floor(($seconds - $days*86400) / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return "$days days: $hours hours: $minutes minutes";
    }
}