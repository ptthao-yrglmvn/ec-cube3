<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// use Codeception\Util\Fixtures;
use Doctrine\ORM\EntityManager;
use Eccube\Entity\Plugin;
use Eccube\Repository\PluginRepository;

class PluginAutomationCest
{  
    /** @var string */
    private $filePath;

    private $config;

    public function _before(AcceptanceTester $I)
    {
        $I->goToAdminPage('admin/');

        $I->submitForm('#form1', [
            'login_id' => 'admin',
            'password' => 'password',
        ]);
        
        $I->see('ホーム', 'h1.page-header');

        // $fileName = 'ProductReview.zip';
        // $this->filePath = '/'.'plugins/'.$fileName;
        $this->filePath = getenv('FILE_PATH');

    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function test_install(AcceptanceTester $I)
    {
        $I->wantTo('Test install plugin');
        Store_Plugin::start($I)->install( $this->filePath);
    }

    
    public function test_enable(AcceptanceTester $I)
    {
        $I->wantTo('Test enable plugin:');
        Store_Plugin::start($I)->enable();
    }

    public function test_disable(AcceptanceTester $I)
    {
        $I->wantTo('Test disable plugin:');
        Store_Plugin::start($I)->disable();
    }

    public function test_remove(AcceptanceTester $I)
    {
        $I->wantTo('Test uninstall plugin:');
        Store_Plugin::start($I)->uninstall();
    }

    public function test_directoryIsRemoved(AcceptanceTester $I)
    {
        $I->wantTo('Test check plugin directory is removed after uninstall:');
        Store_Plugin::start($I)->checkDirectoryIsRemoved();
    }
}


class Store_Plugin
{

    /** @var AcceptanceTester */
    protected $I;


    /** @var \Doctrine\DBAL\Connection */
    protected $conn;

    /** @var Plugin */
    protected $Plugin;

    /** @var EntityManager */
    protected $em;

    /** @var PluginRepository */
    protected $pluginRepository;

    public function __construct(AcceptanceTester $I)
    {
        $this->I = $I;
        // $this->em = Fixtures::get('entityManager');
        // $this->conn = $this->em->getConnection();
        // $this->pluginRepository = $this->em->getRepository(Plugin::class);
    }

    private function getPluginName() {
        return $this->I->grabTextFrom('div.plugin-table tbody td.tp strong');
    }
    private function getPluginCode() {
        return $this->I->grabTextFrom('div.plugin-table tbody td.tc p');
    }

    public static function start(AcceptanceTester $I)
    {
        $result = new self($I);

        return $result;
    }


    public function install($filePath)
    {
        // $this->I->assertFileExists($filePath);

        $this->I->goToAdminPage('admin/store/plugin');
        $this->I->waitForText('オーナーズストア', 10, 'h1.page-header');
        $this->I->see('独自プラグイン','h3.box-title');
        $this->I->click('プラグインのアップロードはこちら');
        $this->I->waitForText('新規プラグインアップロード');
        $this->I->attachFile(['id' => 'plugin_local_install_plugin_archive'], $filePath);
        // Click button Submit
        $this->I->click('#common_box button.btn-primary');
        // Wait for plugin to be installed
        $this->I->wait(5);

        // Verify result
        $this->I->amOnPage('admin/store/plugin');
        $this->I->waitForText('オーナーズストア', 10, 'h1.page-header');
        $this->I->seeElement('div.plugin-table table tbody tr.active');
        $this->I->seeElement('div.plugin-table table tbody tr.active td');
        $elementCount = count($this->I->grabMultiple('div.plugin-table table tbody tr.active td'));
        $this->I->assertEquals($elementCount, 5);

        return $this;
    }

    public function enable()
    {
        $this->I->goToAdminPage('admin/store/plugin');
        $this->I->waitForText('オーナーズストア', 10, 'h1.page-header');
        $this->I->see('独自プラグイン','h3.box-title');
        $pluginName = $this->getPluginName();
        $pluginCode = $this->getPluginCode();

        $this->I->click('有効にする', 'div.plugin-table table');
        $this->I->wait(3);
        $this->I->see('無効にする', 'div.plugin-table table');

        // Check database
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->em->refresh($this->Plugin);
        // $this->I->assertTrue($this->Plugin->isInitialized(), '初期化されている');
        // $this->I->assertTrue($this->Plugin->isEnabled(), '有効化されている');

        return $this;
    }

    public function disable()
    {
        $this->I->goToAdminPage('admin/store/plugin');
        $this->I->waitForText('オーナーズストア', 10, 'h1.page-header');
        $this->I->see('独自プラグイン','h3.box-title');
        $pluginName = $this->getPluginName();
        $pluginCode = $this->getPluginCode();

        $this->I->click('無効にする', 'div.plugin-table table');
        $this->I->wait(3);
        $this->I->see('有効にする', 'div.plugin-table table');
    }

    public function uninstall()
    {
        $this->I->goToAdminPage('admin/store/plugin');
        $this->I->waitForText('オーナーズストア', 10, 'h1.page-header');
        $this->I->see('独自プラグイン','h3.box-title');
        $pluginName = $this->getPluginName();
        $pluginCode = $this->getPluginCode();

        $this->I->click('削除', 'div.plugin-table table');
        $this->I->seeInPopup('このプラグインを削除してもよろしいですか？');
        $this->I->acceptPopup();
        $this->I->wait(5);
        $this->I->see('プラグインを削除しました。','div.alert-success');
        $this->I->see('インストールされているプラグインはありません。','div.text-danger');
        $elementCount = count($this->I->grabMultiple('div.box-body > div.text-danger'));
        $this->I->assertEquals($elementCount, 2);


        // Check database
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->em->refresh($this->Plugin);
        // $this->Plugin = $this->pluginRepository->findByCode($this->code);
        // $this->I->assertNull($this->Plugin, '削除されている');


        return $this;
    }

    public function checkDirectoryIsRemoved()
    {
        // $this->I->assertDirectoryDoesNotExist($this->config['plugin_realdir'].'/'.$this->code);
        return $this;
    }
}