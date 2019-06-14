<?php
namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Controller\TatoebaControllerTestTrait;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class UsersLanguagesControllerTest extends IntegrationTestCase
{
    use TatoebaControllerTestTrait;

    public $fixtures = [
        'app.users',
        'app.users_languages',
    ];


    public function setUp() {
        parent::setUp();
        Configure::write('Acl.database', 'test');
    }

    public function accessesProvider() {
        return [
            // url; user; is accessible or redirection url
           [ '/eng/users_languages/delete/1', null, '/eng/users/login?redirect=%2Feng%2Fusers_languages%2Fdelete%2F1' ],
           [ '/eng/users_languages/delete/1', 'contributor', '/eng/user/profile/contributor' ],
           [ '/eng/users_languages/delete/1', 'kazuki',      '/eng/user/profile/kazuki' ],
        ];
    }

    /**
     * @dataProvider accessesProvider
     */
    public function testControllerAccess($url, $user, $response) {
        $this->assertAccessUrlAs($url, $user, $response);
    }

    private function add_language($langCode) {
        $this->post('/eng/users_languages/save', [
            'id' => '',
            'of_user_id' => '4',
            'language_code' => $langCode,
            'level' => '-1',
            'details' => '',
        ]);
    }

    private function edit_language() {
        $this->post('/eng/users_languages/save/1', [
            'id' => '1',
            'of_user_id' => '4',
            'level' => '2',
            'details' => 'I just leveled up!',
        ]);
    }

    public function testSaveNew_asGuest() {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->add_language('cmn');
        $this->assertRedirect('/eng/users/login');
    }

    public function testSaveNew_asMember() {
        $this->logInAs('contributor');
        $this->add_language('cmn');
        $this->assertRedirect('/eng/user/profile/contributor');
    }

    public function testSaveNew_lang_und() {
        $this->logInAs('contributor');
        $this->add_language('und');
        $this->assertRedirect('/eng/user/language');
    }

    public function testSaveNew_lang_empty() {
        $this->logInAs('contributor');
        $this->add_language('');
        $this->assertRedirect('/eng/user/language');
    }

    public function testSaveExisting_asGuest() {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->edit_language();
        $this->assertRedirect('/eng/users/login');
    }

    public function testSaveExisting_asMember() {
        $this->logInAs('contributor');
        $this->edit_language();
        $this->assertRedirect('/eng/user/profile/contributor');
    }

    public function testSaveExisting_ofOtherUser() {
        $this->logInAs('kazuki');
        $this->edit_language();
        $this->assertRedirect('/eng/user/language');
    }
}
