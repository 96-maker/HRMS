<?php
// app/controllers/MoreController.php

class MoreController {
    public function index(): void {
        $user = AuthService::user();

        if (!$user) {
            redirect('/login');
            return;
        }

        view('more.index', [
            'title' => 'More — DonTech PeopleSuite',
            'user'  => $user
        ]);
    }
}