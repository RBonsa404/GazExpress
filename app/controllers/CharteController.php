<?php
class CharteController extends Controller
{
    public function index(): void
    {
        $this->render('charte/index', ['titre' => 'Charte graphique', 'page' => 'charte']);
    }
}
