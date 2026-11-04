<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_category_management_and_sidebar_marks_it_active(): void
    {
        $admin = $this->admin();
        $category = $this->category('Organic Waste');
        $this->report($this->member(), ['category_id' => $category->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.categories.index'));

        $response
            ->assertOk()
            ->assertSee('Manajemen Kategori')
            ->assertSee('Sampah Organik')
            ->assertDontSee('organic-waste')
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'categories' => 1,
                'usedCategories' => 1,
                'linkedReports' => 1,
            ]);

        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*\/admin\/categories"[^>]*aria-current="page"[^>]*>/',
            $response->getContent(),
        );
    }

    public function test_guest_is_redirected_and_regular_user_is_forbidden_from_category_management(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));

        $user = $this->member();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Unauthorized Category'])
            ->assertForbidden();
    }

    public function test_admin_can_create_category_and_slug_is_generated_from_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name' => 'Household Organic Waste',
                'description' => 'Sisa bahan alami dari rumah tangga.',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Household Organic Waste',
            'slug' => 'household-organic-waste',
            'description' => 'Sisa bahan alami dari rumah tangga.',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Household-Organic Waste'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Household-Organic Waste',
            'slug' => 'household-organic-waste-2',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['description' => 'Deskripsi saja'])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('waste_categories', 0);
    }

    #[DataProvider('categoryWriteOperations')]
    public function test_slug_conflicts_after_precheck_are_retried(string $operation): void
    {
        $this->actingAs($this->admin());
        $category = $operation === 'update' ? $this->category('Original Category') : null;
        $attempts = 0;

        $this->duringCategorySave(function (WasteCategory $candidate) use (&$attempts): void {
            $attempts++;
            if ($attempts <= 2) {
                // Simulasi penulis lain setelah precheck, memakai constraint SQLite sebenarnya.
                DB::table('waste_categories')->insert([
                    'name' => 'Concurrent category '.$attempts,
                    'slug' => $candidate->slug,
                ]);
            }
        }, function () use ($category): void {
            $this->writeCategory($category)
                ->assertRedirect(route('admin.categories.index'))
                ->assertSessionHas('status');
        });

        $this->assertSame(3, $attempts);
        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Organic Waste!', 'slug' => 'organic-waste-3', 'description' => 'Updated description',
        ]);
        $this->assertDatabaseCount('waste_categories', 3);
        if ($category) {
            $this->assertSame('organic-waste-3', $category->fresh()->slug);
        }
    }

    #[DataProvider('categoryWriteOperations')]
    public function test_slug_conflicts_stop_after_three_attempts(string $operation): void
    {
        $this->actingAs($this->admin());
        $category = $operation === 'update' ? $this->category('Original Category') : null;
        $attempts = 0;

        $this->duringCategorySave(function (WasteCategory $candidate) use (&$attempts): void {
            $attempts++;
            DB::table('waste_categories')->insert([
                'name' => 'Concurrent category '.$attempts,
                'slug' => $candidate->slug,
            ]);
        }, function () use ($category): void {
            $this->writeCategory($category)->assertStatus(500);
        });

        $this->assertSame(3, $attempts);
        $this->assertDatabaseMissing('waste_categories', ['name' => 'Organic Waste!']);
        $this->assertDatabaseCount('waste_categories', $category ? 4 : 3);
        if ($category) {
            $this->assertSame('original-category', $category->fresh()->slug);
            $this->assertNull($category->fresh()->description);
        }
    }

    #[DataProvider('categoryWriteOperations')]
    public function test_unrelated_unique_constraint_errors_are_not_retried(string $operation): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $category = $operation === 'update' ? $this->category('Original Category') : null;
        $attempts = 0;

        $this->duringCategorySave(function () use ($admin, &$attempts): void {
            $attempts++;
            DB::table('users')->insert([
                'name' => 'Duplicate email', 'email' => $admin->email, 'password' => 'unused',
            ]);
        }, function () use ($category): void {
            $this->writeCategory($category)->assertStatus(500);
        });

        $this->assertSame(1, $attempts);
        $this->assertDatabaseMissing('waste_categories', ['name' => 'Organic Waste!']);
        $this->assertDatabaseCount('waste_categories', $category ? 1 : 0);
        if ($category) {
            $this->assertSame('original-category', $category->fresh()->slug);
        }
    }

    public static function categoryWriteOperations(): array
    {
        return ['create' => ['create'], 'update' => ['update']];
    }

    private function duringCategorySave(callable $listener, callable $request): void
    {
        $dispatcher = WasteCategory::getEventDispatcher();
        WasteCategory::setEventDispatcher(clone $dispatcher);

        try {
            WasteCategory::saving($listener);
            $request();
        } finally {
            WasteCategory::setEventDispatcher($dispatcher);
        }
    }

    private function writeCategory(?WasteCategory $category): \Illuminate\Testing\TestResponse
    {
        $payload = ['name' => 'Organic Waste!', 'description' => 'Updated description'];

        return $category
            ? $this->put(route('admin.categories.update', $category), $payload)
            : $this->post(route('admin.categories.store'), $payload);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $this->category('Paper Waste');
        $plastic = $this->category('Plastic Waste');

        $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Paper Waste'])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin())
            ->from(route('admin.categories.edit', $plastic))
            ->put(route('admin.categories.update', $plastic), ['name' => 'Paper Waste'])
            ->assertRedirect(route('admin.categories.edit', $plastic))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('waste_categories', 2);
    }

    public function test_admin_can_update_category_and_its_generated_slug(): void
    {
        $category = $this->category('Custom Organic Waste');

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name' => 'Food Scraps',
                'description' => 'Sisa makanan dan bahan organik.',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('waste_categories', [
            'id' => $category->id,
            'name' => 'Food Scraps',
            'slug' => 'food-scraps',
            'description' => 'Sisa makanan dan bahan organik.',
        ]);
    }

    public function test_admin_can_delete_category_that_is_not_used_by_reports(): void
    {
        $category = $this->category('Unused Category');

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('waste_categories', ['id' => $category->id]);
    }

    public function test_category_used_as_confirmed_or_ai_prediction_cannot_be_deleted(): void
    {
        $confirmedCategory = $this->category('Confirmed Category');
        $predictedCategory = $this->category('Predicted Category');
        $owner = $this->member();
        $report = $this->report($owner, [
            'category_id' => $confirmedCategory->id,
            'ai_category_id' => $predictedCategory->id,
        ]);

        foreach ([$confirmedCategory, $predictedCategory] as $category) {
            $this->actingAs($this->admin())
                ->delete(route('admin.categories.destroy', $category))
                ->assertRedirect(route('admin.categories.index'))
                ->assertSessionHas('error');

            $this->assertDatabaseHas('waste_categories', ['id' => $category->id]);
            $this->assertDatabaseHas('waste_reports', ['id' => $report->id]);
        }
    }

    public function test_category_list_uses_real_confirmed_and_ai_report_counts_without_double_counting_summary(): void
    {
        $organic = $this->category('Organic Waste');
        $plastic = $this->category('Plastic Waste');
        $owner = $this->member();
        $this->report($owner, [
            'category_id' => $organic->id,
            'ai_category_id' => $plastic->id,
        ]);
        $this->report($owner, ['ai_category_id' => $organic->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'categories' => 2,
                'usedCategories' => 2,
                'linkedReports' => 2,
            ])
            ->assertViewHas('categories', function ($categories): bool {
                $organic = $categories->firstWhere('slug', 'organic-waste');

                return $organic?->reports_count === 1 && $organic?->ai_predicted_reports_count === 1;
            });
    }

    public function test_default_category_labels_and_indonesian_search_preserve_internal_data(): void
    {
        $admin = $this->admin();
        $categories = [
            'Organic Waste' => 'Sampah Organik',
            'Paper Waste' => 'Sampah Kertas',
            'Plastic Waste' => 'Sampah Plastik',
        ];

        foreach ($categories as $name => $label) {
            $category = $this->category($name);
            $response = $this->actingAs($admin)->get(route('admin.categories.index', ['search' => $label]));
            $response->assertOk()->assertSee($label)->assertDontSee($name)->assertDontSee($category->slug)
                ->assertViewHas('categories', fn ($rows): bool => $rows->total() === 1 && $rows->first()->is($category));
            $this->assertDatabaseHas('waste_categories', ['id' => $category->id, 'name' => $name, 'slug' => $category->slug]);
        }

        $custom = $this->category('Sampah Elektronik');
        $this->get(route('admin.categories.index', ['search' => 'elektronik']))
            ->assertOk()->assertSee($custom->name)->assertDontSee($custom->slug);
        $this->get(route('admin.categories.index', ['search' => 'organic-waste']))
            ->assertOk()->assertSee('Sampah Organik');
    }

    public function test_seeded_categories_keep_their_identity_but_allow_description_updates(): void
    {
        $this->seed(\Database\Seeders\WasteCategorySeeder::class);
        $this->actingAs($this->admin());

        foreach (WasteCategory::all() as $category) {
            $identity = $category->only(['id', 'name', 'slug']);
            $description = $category->description;
            $this->delete(route('admin.categories.destroy', $category))
                ->assertRedirect(route('admin.categories.index'))->assertSessionHas('error');
            $this->assertDatabaseHas('waste_categories', $identity);

            $this->put(route('admin.categories.update', $category), [
                'name' => 'Nama pengganti', 'description' => 'Tidak boleh tersimpan',
            ])->assertSessionHasErrors('name');
            $this->put(route('admin.categories.update', $category), [
                'name' => $category->name, 'slug' => 'slug-pengganti',
            ])->assertSessionHasErrors('slug');
            $this->assertDatabaseHas('waste_categories', [...$identity, 'description' => $description]);

            $this->put(route('admin.categories.update', $category), [
                'name' => $category->name, 'description' => 'Deskripsi diperbarui',
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));
            $this->assertDatabaseHas('waste_categories', [...$identity, 'description' => 'Deskripsi diperbarui']);
        }
        $this->assertDatabaseCount('waste_categories', 3);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function member(): User
    {
        return User::factory()->create();
    }

    private function category(string $name): WasteCategory
    {
        return WasteCategory::query()->create([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);
    }

    /** @param array{category_id?: int|null, ai_category_id?: int|null} $attributes */
    private function report(User $owner, array $attributes = []): WasteReport
    {
        return WasteReport::query()->create(array_merge([
            'user_id' => $owner->id,
            'description' => 'Laporan uji kategori',
            'image_path' => 'reports/category-test.jpg',
        ], $attributes));
    }
}
