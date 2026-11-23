<?php

return [
    'admin_session_idle_minutes' => (int) env('ADMIN_SESSION_IDLE_MINUTES', 30),
    'cashier_session_idle_minutes' => (int) env('CASHIER_SESSION_IDLE_MINUTES', 30),
];
