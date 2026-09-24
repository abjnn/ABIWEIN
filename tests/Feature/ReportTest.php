<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FarmReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vérifie l'archive formelle, les rôles et le téléchargement PDF des rapports. */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /** Le responsable peut générer un snapshot figé depuis le formulaire. */
    public function test_manager_can_generate_an_archived_report(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $response = $this->actingAs($manager)->post(route('reports.store'), [
            'type' => 'summary',
            'title' => 'Situation générale de septembre',
            'report_date' => '2026-09-21',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-21',
        ]);

        $report = FarmReport::query()->sole();

        $response->assertRedirect(route('reports.show', $report));
        $this->assertSame($manager->id, $report->generated_by);
        $this->assertSame('Situation générale de septembre', $report->title);
        $this->assertNotEmpty(data_get($report->snapshot, 'sections'));
        $this->assertDatabaseHas('farm_reports', ['reference' => $report->reference]);
    }

    /** L'administrateur lit les documents mais ne peut pas en créer. */
    public function test_administrator_has_read_only_report_access(): void
    {
        $administrator = User::factory()->administrator()->create();
        $report = $this->createReport($administrator, ['reference' => 'RPT-ADMIN-READ']);

        $this->actingAs($administrator)->get(route('reports.index'))->assertOk();
        $this->actingAs($administrator)->get(route('reports.show', $report))->assertOk();
        $this->actingAs($administrator)->get(route('reports.create'))->assertForbidden();
        $this->actingAs($administrator)->post(route('reports.store'), [])->assertForbidden();
    }

    /** Le vétérinaire ne reçoit aucun accès aux rapports financiers ou administratifs. */
    public function test_veterinarian_cannot_access_reports(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $report = $this->createReport($manager);

        $this->actingAs($veterinarian)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($veterinarian)->get(route('reports.show', $report))->assertForbidden();
        $this->actingAs($veterinarian)->get(route('reports.pdf', $report))->assertForbidden();
    }

    /** La liste place le plus récent en premier et respecte les filtres demandés. */
    public function test_report_list_is_sorted_and_filterable(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->createReport($administrator, [
            'reference' => 'RPT-OLD-001',
            'type' => 'daily',
            'title' => 'Ancien rapport',
            'report_date' => '2026-09-01',
        ]);
        $nearbyReport = $this->createReport($administrator, [
            'reference' => 'RPT-NEAR-001',
            'type' => 'health',
            'title' => 'Rapport de la veille',
            'report_date' => '2026-09-19',
        ]);
        $recentReport = $this->createReport($administrator, [
            'reference' => 'RPT-NEW-001',
            'type' => 'health',
            'title' => 'Rapport récent',
            'report_date' => '2026-09-20',
        ]);

        $this->actingAs($administrator)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeInOrder(['RPT-NEW-001', 'RPT-NEAR-001', 'RPT-OLD-001'])
            ->assertSee('name="date"', false)
            ->assertDontSee('name="date_from"', false)
            ->assertDontSee('name="date_to"', false)
            // Le raccourci PDF a disparu de la liste : le téléchargement reste sur la fiche.
            ->assertDontSee(route('reports.pdf', $recentReport))
            ->assertDontSee(route('reports.pdf', $nearbyReport));

        $this->actingAs($administrator)
            ->get(route('reports.index', [
                'date' => '2026-09-20',
                'type' => 'health',
            ]))
            ->assertOk()
            ->assertSee('RPT-NEW-001')
            ->assertDontSee('RPT-NEAR-001')
            ->assertDontSee('RPT-OLD-001');
    }

    /** La page détail ressemble à un document et restitue le snapshot sauvegardé. */
    public function test_report_detail_displays_formal_archived_content(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager, 'name' => 'Alain Tchoumi']);
        $report = $this->createReport($manager, [
            'reference' => 'RPT-FORMAL-001',
            'title' => 'Rapport sanitaire hebdomadaire',
        ]);

        $this->actingAs($manager)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSeeText('Document administratif')
            ->assertSeeText('RPT-FORMAL-001')
            ->assertSeeText('Situation archivée de contrôle')
            ->assertSeeText('Alain Tchoumi')
            ->assertSeeText('Télécharger le PDF')
            ->assertSee(route('reports.pdf', $report))
            ->assertDontSee('<canvas', false);
    }

    /** Le bouton PDF retourne un vrai fichier PDF téléchargeable. */
    public function test_report_can_be_downloaded_as_a_real_pdf(): void
    {
        $administrator = User::factory()->administrator()->create();
        $report = $this->createReport($administrator, ['reference' => 'RPT-PDF-001']);

        $response = $this->actingAs($administrator)->get(route('reports.pdf', $report));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('RPT-PDF-001.pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    /** Fabrique un rapport minimal sans dépendre des autres modules métier. */
    private function createReport(User $generator, array $overrides = []): FarmReport
    {
        return FarmReport::query()->create(array_merge([
            'reference' => 'RPT-TEST-001',
            'type' => 'summary',
            'title' => 'Rapport de contrôle',
            'report_date' => '2026-09-21',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-21',
            'snapshot' => [
                'version' => 1,
                'generated_at' => '2026-09-21T10:00:00+01:00',
                'purpose' => 'Situation archivée de contrôle.',
                'sections' => [[
                    'title' => 'Synthèse',
                    'paragraphs' => ['Situation archivée de contrôle.'],
                    'metrics' => [['label' => 'Bandes actives', 'value' => '2']],
                    'tables' => [],
                ]],
            ],
            'generated_by' => $generator->id,
        ], $overrides));
    }
}
