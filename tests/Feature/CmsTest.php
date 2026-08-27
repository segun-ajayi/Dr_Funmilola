<?php
namespace Tests\Feature;
use App\Enums\UserRole;
use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CmsTest extends TestCase
{
 use RefreshDatabase;
 public function test_only_power_admin_can_access_editor_and_protected_slugs_are_rejected():void{Sanctum::actingAs(User::factory()->create(['role'=>UserRole::Admin]));$this->getJson('/api/cms/pages')->assertForbidden();Sanctum::actingAs($this->power());$this->postJson('/api/cms/pages',['title'=>'Portal','slug'=>'portal','template'=>'standard'])->assertUnprocessable();}
 public function test_structured_sections_reject_html_and_unsupported_fields():void{$page=$this->page();$payload=['type'=>'hero','content'=>['heading'=>'Safe heading','text'=>'Helpful introduction'],'presentation'=>['background'=>'ivory','alignment'=>'left'],'is_visible'=>true];$this->postJson("/api/cms/pages/{$page->id}/sections",$payload)->assertCreated();$payload['content']['heading']='<script>alert(1)</script>';$this->postJson("/api/cms/pages/{$page->id}/sections",$payload)->assertUnprocessable();$payload['content']=['heading'=>'Safe','raw_html'=>'<b>no</b>'];$this->postJson("/api/cms/pages/{$page->id}/sections",$payload)->assertUnprocessable();}
 public function test_draft_is_private_preview_is_time_bounded_and_publish_is_explicit():void{$page=$this->page();$this->section($page,'Original heading');$this->getJson('/api/cms/pages/'.$page->slug)->assertNotFound();$preview=$this->postJson("/api/cms/pages/{$page->id}/preview")->assertOk()->json('preview_url');$token=basename(parse_url($preview,PHP_URL_PATH));$this->getJson('/api/cms/preview/'.$token)->assertOk()->assertJsonPath('data.sections.0.content.heading','Original heading');CmsPreviewToken::query()->update(['expires_at'=>now()->subMinute()]);$this->getJson('/api/cms/preview/'.$token)->assertNotFound();$this->postJson("/api/cms/pages/{$page->id}/publish")->assertOk();$this->getJson('/api/cms/pages/'.$page->slug)->assertOk()->assertJsonPath('data.sections.0.content.heading','Original heading');}
 public function test_version_restore_creates_new_draft_without_changing_published_snapshot():void{$page=$this->page();$section=$this->section($page,'Version one');$firstVersion=$page->versions()->latest('version')->first();$this->postJson("/api/cms/pages/{$page->id}/publish");$this->putJson("/api/cms/pages/{$page->id}/sections/{$section->id}",['type'=>'hero','content'=>['heading'=>'Version two'],'presentation'=>[],'is_visible'=>true])->assertOk();$this->postJson("/api/cms/pages/{$page->id}/versions/{$firstVersion->id}/restore")->assertOk()->assertJsonPath('data.sections.0.content.heading','Version one');$this->assertSame('Version one',CmsPage::find($page->id)->published_snapshot['sections'][0]['content']['heading']);$this->assertGreaterThan(3,$page->versions()->count());}
 public function test_navigation_and_theme_settings_are_allowlisted():void{$this->power();$this->putJson('/api/cms/settings/navigation',['value'=>[['label'=>'Care','path'=>'/services']]])->assertOk();$this->putJson('/api/cms/settings/navigation',['value'=>[['label'=>'Bad','path'=>'javascript:alert(1)']]])->assertUnprocessable();$this->putJson('/api/cms/settings/theme',['value'=>['palette'=>'wine','density'=>'comfortable','heading_style'=>'editorial']])->assertOk();$this->postJson('/api/cms/settings/theme/publish')->assertOk();$this->getJson('/api/cms/public-settings')->assertOk()->assertJsonPath('data.theme.palette','wine');}
 private function power():User{$user=User::factory()->create(['role'=>UserRole::PowerAdmin]);Sanctum::actingAs($user);return$user;}
 private function page():CmsPage{$user=$this->power();return CmsPage::create(['title'=>'Living well','slug'=>'living-well','template'=>'standard','created_by'=>$user->id]);}
 private function section(CmsPage $page,string $heading){$response=$this->postJson("/api/cms/pages/{$page->id}/sections",['type'=>'hero','content'=>['heading'=>$heading],'presentation'=>[],'is_visible'=>true])->assertCreated();return \App\Models\CmsSection::findOrFail($response->json('data.id'));}
}
