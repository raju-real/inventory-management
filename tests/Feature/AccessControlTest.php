<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_admin_can_open_every_menu_page()
    {
        $routes = Menu::whereNotNull('route')->pluck('route');
        $this->assertGreaterThan(20, $routes->count());

        foreach ($routes as $route) {
            $this->actingAs($this->admin())->get(route($route))->assertOk();
        }
    }

    public function test_guests_cannot_reach_any_page()
    {
        foreach (Menu::whereNotNull('route')->pluck('route') as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_a_user_without_permissions_is_refused_everywhere()
    {
        $user = $this->staff();

        foreach (Menu::whereNotNull('route')->where('is_public', false)->pluck('route') as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->get(route('profile.edit'))->assertOk();
        $this->get('/')->assertRedirect(route('profile.edit'));
    }

    public function test_actions_are_checked_one_by_one()
    {
        $product = $this->product();
        $user = $this->staff(['products' => ['view']]);

        $this->actingAs($user)->get(route('products.index'))->assertOk()->assertDontSee('Add Product');
        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), [])->assertForbidden();
        $this->get(route('products.edit', $product))->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();
        $this->get(route('sales.index'))->assertForbidden();

        $user2 = $this->staff(['products' => ['view', 'create']]);
        $this->actingAs($user2)->get(route('products.index'))->assertSee('Add Product');
        $this->get(route('products.create'))->assertOk();
    }

    public function test_the_sidebar_toggle_is_on_the_page_and_remembered_per_user()
    {
        $admin = $this->admin();
        $page = $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
        $page->assertSee('data-sidebar-toggle', false);
        $page->assertSee('data-sidebar-key="sidebar-collapsed:' . $admin->unique_id . '"', false);
    }

    public function test_the_sidebar_only_lists_assigned_menus()
    {
        $user = $this->staff(['sales' => ['view'], 'products' => ['view']]);

        $response = $this->actingAs($user)->get(route('sales.index'))->assertOk();
        $response->assertSee('>Sales<', false)->assertSee('>Products<', false);
        $response->assertDontSee('>Purchases<', false)->assertDontSee('>Users<', false)->assertDontSee('>Reports<', false);
        $this->assertSame('products.index', $user->homeRoute());
    }

    public function test_cancel_needs_the_delete_permission()
    {
        $product = $this->product();
        $this->postSale($product, 1);
        $sale = \App\Models\Sale::latest('id')->first();

        $viewer = $this->staff(['sales' => ['view', 'create']]);
        $this->actingAs($viewer)->post(route('sales.cancel', $sale), ['cancel_reason' => 'x'])->assertForbidden();
        $this->assertSame('posted', $sale->fresh()->status);
    }

    public function test_admin_can_assign_menus_and_it_applies_immediately()
    {
        $user = $this->staff();
        $products = Menu::where('key', 'products')->first();
        $sales = Menu::where('key', 'sales')->first();

        $this->actingAs($user)->get(route('products.index'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('users.access', $user))->assertOk()->assertSee('Save Access');
        $this->put(route('users.access.update', $user), ['permissions' => [
            $products->unique_id => ['create'],
            $sales->unique_id => ['view'],
        ]])->assertSessionHasNoErrors();

        $user = $user->fresh();
        // Choosing an action always includes view.
        $this->assertTrue($user->canAccess('products', 'view'));
        $this->assertTrue($user->canAccess('products', 'create'));
        $this->assertFalse($user->canAccess('products', 'edit'));
        $this->actingAs($user)->get(route('products.index'))->assertOk();

        $this->actingAs($this->admin())->put(route('users.access.update', $user), ['permissions' => [$sales->unique_id => ['view']]]);
        $this->actingAs($user->fresh())->get(route('products.index'))->assertForbidden();
        $this->get(route('sales.index'))->assertOk();
    }

    public function test_admin_only_pages_cannot_be_assigned()
    {
        $user = $this->staff();
        $users = Menu::where('key', 'users')->first();

        $this->actingAs($this->admin())->put(route('users.access.update', $user), ['permissions' => [$users->unique_id => ['view']]]);
        $this->assertSame(0, $user->menuPermissions()->count());

        foreach (['users.index', 'menus.index', 'settings.edit', 'barcode.settings'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
        $this->post(route('users.store'), ['role' => 'admin'])->assertForbidden();
        $this->put(route('users.access.update', $user), ['permissions' => []])->assertForbidden();
    }

    public function test_a_hidden_menu_disappears_for_assigned_users()
    {
        $user = $this->staff(['products' => ['view']]);
        $menu = Menu::where('key', 'products')->first();

        $this->actingAs($this->admin())->put(route('menus.update', $menu), ['name' => 'Goods', 'icon' => 'bi-box', 'sort_order' => 3, 'parent_id' => $menu->parent->unique_id, 'is_active' => 0])->assertSessionHasNoErrors();
        $this->actingAs($user->fresh())->get(route('products.index'))->assertForbidden();

        $this->actingAs($this->admin())->put(route('menus.update', $menu), ['name' => 'Goods', 'icon' => 'bi-box', 'sort_order' => 3, 'parent_id' => $menu->parent->unique_id, 'is_active' => 1]);
        $this->actingAs($user->fresh())->get(route('products.index'))->assertOk()->assertSee('>Goods<', false);
    }

    public function test_admin_menus_cannot_be_hidden()
    {
        $menu = Menu::where('key', 'users')->first();

        $this->actingAs($this->admin())->put(route('menus.update', $menu), ['name' => 'Users', 'icon' => 'bi-person', 'sort_order' => 1, 'parent_id' => $menu->parent->unique_id, 'is_active' => 0])
            ->assertSessionHasErrors('is_active');
    }

    public function test_a_deactivated_user_is_logged_out_and_cannot_log_in()
    {
        $user = $this->staff(['products' => ['view']], ['password' => bcrypt('secret-123')]);

        $user->update(['status' => false]);
        $this->actingAs($user)->get(route('products.index'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_works_and_is_throttled()
    {
        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'Admin@12345'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->admin());
        $this->post(route('logout'));
        $this->assertGuest();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'wrong']);
        }
        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'Admin@12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_forgotten_password_can_be_reset_by_email()
    {
        \Illuminate\Support\Facades\Notification::fake();
        $admin = $this->admin();

        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['email' => $admin->email])->assertSessionHas('success');

        $token = null;
        \Illuminate\Support\Facades\Notification::assertSentTo($admin, \Illuminate\Auth\Notifications\ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get(route('password.reset', $token))->assertOk();
        $this->post(route('password.update'), ['token' => $token, 'email' => $admin->email, 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'brand-new-pass'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_the_reset_form_does_not_reveal_which_emails_have_accounts()
    {
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('success');
    }

    public function test_the_last_admin_is_protected()
    {
        $admin = $this->admin();
        $payload = ['name' => 'Admin', 'email' => $admin->email, 'role' => 'user', 'status' => 1];

        $this->actingAs($admin)->put(route('users.update', $admin), $payload)->assertSessionHasErrors('role');
        $this->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_create_a_user_who_can_then_log_in()
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'New', 'email' => 'new@example.com', 'role' => 'user', 'status' => 1,
            'password' => 'password-123', 'password_confirmation' => 'password-123',
        ])->assertRedirect();

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'new@example.com', 'password' => 'password-123'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $this->assertSame('user', User::where('email', 'new@example.com')->first()->role);
    }

    public function test_profile_password_change_needs_the_current_password()
    {
        $user = $this->staff([], ['password' => bcrypt('secret-123')]);
        $base = ['name' => 'Staff', 'email' => $user->email];

        $this->actingAs($user)->put(route('profile.update'), $base + ['password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertSessionHasErrors('current_password');
        $this->put(route('profile.update'), $base + ['current_password' => 'secret-123', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-pass-123', $user->fresh()->password));
    }
}
