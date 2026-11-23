<?php
namespace Tests\\Feature;
use Tests\\TestCase;
class HomePageTest extends TestCase {
 public function test_homepage_is_available_in_arabic(): void {
  $this->get('/')->assertOk()->assertSee('نظام فتوش لإدارة المطعم')->assertSee('dir="rtl"',false);
 }
 public function test_health_endpoint_is_available(): void {$this->get('/up')->assertOk();}
}
