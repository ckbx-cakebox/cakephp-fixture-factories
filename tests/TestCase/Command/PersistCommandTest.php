<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         2.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakephpFixtureFactories\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakephpFixtureFactories\Command\PersistCommand;
use CakephpFixtureFactories\Test\Factory\AddressFactory;
use CakephpFixtureFactories\Test\Factory\ArticleFactory;
use CakephpFixtureFactories\Test\Factory\BillFactory;
use CakephpFixtureFactories\Test\Factory\CityFactory;
use CakephpFixtureFactories\Test\Factory\CountryFactory;

class PersistCommandTest extends TestCase
{
    /**
     * @var PersistCommand
     */
    public $command;
    /**
     * @var ConsoleIo
     */
    public $io;

    public static function setUpBeforeClass(): void
    {
        Configure::write('FixtureFactories.testFixtureNamespace', 'CakephpFixtureFactories\Test\Factory');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new PersistCommand();
        $this->io  = new ConsoleIo();
        $this->io->level(ConsoleIo::QUIET);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->command);
        unset($this->io);
    }

    public function dataProviderForStringFactories(): array
    {
        return [
          ['Articles'],
          ['Article'],
          [ArticleFactory::class],
        ];
    }

    /**
     * @dataProvider dataProviderForStringFactories
     */
    public function testPersistOnOneFactory(string $factoryString)
    {
        $args = new Arguments([$factoryString], [], [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertSame(1, ArticleFactory::count());
    }

    public function dataProviderForStringPluginFactories(): array
    {
        return [
            ['TestPlugin.Bills'],
            ['TestPlugin.Bill'],
            [BillFactory::class],
        ];
    }

    /**
     * @dataProvider dataProviderForStringPluginFactories
     */
    public function testPersistOnOnePluginFactory(string $factoryString)
    {
        $args = new Arguments([$factoryString], [], [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertSame(1, BillFactory::count());
    }

    /**
     * @dataProvider dataProviderForStringFactories
     */
    public function testPersistOnNFactories(string $factoryString)
    {
        $number = '3';
        $args = new Arguments([$factoryString], compact('number'), [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertEquals($number, ArticleFactory::count());
    }

    public function testPersistWithMethodAndNumber()
    {
        $number = '3';
        $args = new Arguments(['Article'], ['method' => 'withBills', 'number' => $number], [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertEquals($number, ArticleFactory::count());
        $this->assertEquals($number, BillFactory::count());
    }

    public function testPersistWithAssociation()
    {
        $numberOfCities = 3;
        $numberOfAddresses = 4;
        $args = new Arguments(['Country'], ['with' => "Cities[$numberOfCities].Addresses[$numberOfAddresses]"], [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertEquals(1, CountryFactory::count());
        $this->assertEquals($numberOfCities, CityFactory::count());
        $this->assertEquals($numberOfCities * $numberOfAddresses, AddressFactory::count());
    }

    public function testPersistWithMethodAndNumberDryRun()
    {
        $number = '3';
        $args = new Arguments(['Article'], ['method' => 'withBills', 'number' => $number, 'dry-run' => true], [PersistCommand::ARG_NAME]);

        $output = $this->command->execute($args, $this->io);

        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $this->assertSame(0, ArticleFactory::count());
        $this->assertSame(0, BillFactory::count());
    }

    public function testPersistWithWrongFactory()
    {
        $className = 'foo';
        $args = new Arguments([$className], [], [PersistCommand::ARG_NAME]);

        $this->expectException(StopException::class);
        $this->command->execute($args, $this->io);
    }

    public function testPersistWithWrongMethod()
    {
        $className = ArticleFactory::class;
        $method = 'foo';
        $args = new Arguments([$className], compact('method'), [PersistCommand::ARG_NAME]);

        $this->expectException(StopException::class);
        $this->command->execute($args, $this->io);
    }

    /**
     * @see /tests/bootstrap.php
     */
    public function testAliasedConnection()
    {
        $output = $this->command->execute(new Arguments([ArticleFactory::class], [], [PersistCommand::ARG_NAME]), $this->io);
        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $configName = TableRegistry::getTableLocator()->get('TestPlugin.Bills')->getConnection()->configName();
        $this->assertSame('test', $configName);

        $output = $this->command->execute(new Arguments([ArticleFactory::class], ['connection' => 'dummy'], [PersistCommand::ARG_NAME]), $this->io);
        $this->assertSame(PersistCommand::CODE_SUCCESS, $output);
        $dummyKeyValue = ConnectionManager::get('test')->config()['dummy_key'];
        $this->assertSame('DummyKeyValue', $dummyKeyValue);
    }
}
