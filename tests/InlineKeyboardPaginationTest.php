<?php

namespace TelegramBot\InlineKeyboardPagination\Tests;

use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

/**
 * Class InlineKeyboardPaginationTest
 */
final class InlineKeyboardPaginationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var int
     */
    private $items_per_page = 5;

    /**
     * @var int
     */
    private $selected_page;

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $items;

    /**
     * InlineKeyboardPaginationTest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->items         = range(1, 100);
        $this->command       = 'testCommand';
        $this->selected_page = random_int(1, 15);
    }

    public function testValidConstructor()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, $this->selected_page, $this->items_per_page);

        $data = $ikp->getPagination();

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($this->items_per_page, $data['items']);
        $this->assertArrayHasKey('keyboard', $data);
        $this->assertArrayHasKey(1, $data['keyboard'][0]);
        $this->assertArrayHasKey('text', $data['keyboard'][0][1]);
        $this->assertStringStartsWith($this->command, $data['keyboard'][0][1]['callback_data']);
    }

    /**
     * @expectedException \TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException
     * @expectedExceptionMessage Invalid selected page, must be between 1 and 20
     */
    public function testInvalidConstructor()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, 10000, $this->items_per_page);
        $ikp->getPagination();
    }

    /**
     * @expectedException \TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException
     * @expectedExceptionMessage Items list empty.
     */
    public function testEmptyItemsConstructor()
    {
        $ikp = new InlineKeyboardPagination([]);
        $ikp->getPagination();
    }

    public function testValidPagination()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, $this->selected_page, $this->items_per_page);

        $length = (int) ceil(count($this->items) / $this->items_per_page);

        for ($i = 1; $i < $length; $i++) {
            $ikp->getPagination($i);
        }

        $this->assertTrue(true);
    }

    /**
     * @expectedException \TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException
     * @expectedExceptionMessage Invalid selected page, must be between 1 and 20
     */
    public function testInvalidPagination()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, $this->selected_page, $this->items_per_page);
        $ikp->getPagination($ikp->getNumberOfPages() + 1);
    }

    /**
     * @expectedException \TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException
     * @expectedExceptionMessage Invalid max buttons, must be between 5 and 8.
     */
    public function testInvalidMaxButtons()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, $this->selected_page, $this->items_per_page);
        $ikp->setMaxButtons(2);
        $ikp->getPagination();
    }

    /**
     * @expectedException \TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException
     * @expectedExceptionMessage Invalid selected page, must be between 1 and 20
     */
    public function testInvalidSelectedPage()
    {
        $ikp = new InlineKeyboardPagination($this->items, $this->command, $this->selected_page, $this->items_per_page);
        $ikp->setSelectedPage(-5);
        $ikp->getPagination();
    }

    public function testButtonLabels()
    {
        $cbdata  = '%s?currentPage=%d&nextPage=%d';
        $command = 'cbdata';
        $ikp1    = new InlineKeyboardPagination(range(1, 1), $command, 1, $this->items_per_page);
        $ikp10   = new InlineKeyboardPagination(range(1, $this->items_per_page * 10), $command, 1, $this->items_per_page);

        // current
        $keyboard = $ikp1->getPagination(1)['keyboard'];
        self::assertAllButtonPropertiesEqual([
            ['· 1 ·'],
        ], 'text', $keyboard);
        self::assertAllButtonPropertiesEqual([
            [
                sprintf($cbdata, $command, 1, 1),
            ],
        ], 'callback_data', $keyboard);

        // first, previous, current, next, last
        $keyboard = $ikp10->getPagination(5)['keyboard'];
        self::assertAllButtonPropertiesEqual([
            ['« 1', '‹ 4', '· 5 ·', '6 ›', '10 »'],
        ], 'text', $keyboard);
        self::assertAllButtonPropertiesEqual([
            [
                sprintf($cbdata, $command, 5, 1),
                sprintf($cbdata, $command, 5, 4),
                sprintf($cbdata, $command, 5, 5),
                sprintf($cbdata, $command, 5, 6),
                sprintf($cbdata, $command, 5, 10),
            ],
        ], 'callback_data', $keyboard);

        // first, previous, current, last
        $keyboard = $ikp10->getPagination(9)['keyboard'];
        self::assertAllButtonPropertiesEqual([
            ['« 1', '‹ 7', '8', '· 9 ·', '10'],
        ], 'text', $keyboard);
        self::assertAllButtonPropertiesEqual([
            [
                sprintf($cbdata, $command, 9, 1),
                sprintf($cbdata, $command, 9, 7),
                sprintf($cbdata, $command, 9, 8),
                sprintf($cbdata, $command, 9, 9),
                sprintf($cbdata, $command, 9, 10),
            ],
        ], 'callback_data', $keyboard);

        // first, previous, current
        $keyboard = $ikp10->getPagination(10)['keyboard'];
        self::assertAllButtonPropertiesEqual([
            ['« 1', '‹ 7', '8', '9', '· 10 ·'],
        ], 'text', $keyboard);
        self::assertAllButtonPropertiesEqual([
            [
                sprintf($cbdata, $command, 10, 1),
                sprintf($cbdata, $command, 10, 7),
                sprintf($cbdata, $command, 10, 8),
                sprintf($cbdata, $command, 10, 9),
                sprintf($cbdata, $command, 10, 10),
            ],
        ], 'callback_data', $keyboard);

        // custom labels, skipping some buttons
        // first, previous, current, next, last
        $ikp10->setLabels([
            'first'    => '',
            'previous' => 'previous %d',
            'current'  => null,
            'next'     => '%d next',
            'last'     => 'last',
        ]);
        $keyboard = $ikp10->getPagination(5)['keyboard'];
        self::assertAllButtonPropertiesEqual([
            ['previous 4', '6 next', 'last'],
        ], 'text', $keyboard);
        self::assertAllButtonPropertiesEqual([
            [
                sprintf($cbdata, $command, 5, 4),
                sprintf($cbdata, $command, 5, 6),
                sprintf($cbdata, $command, 5, 10),
            ],
        ], 'callback_data', $keyboard);
    }

    public static function assertButtonPropertiesEqual($value, $property, $keyboard, $row, $column, $message = '')
    {
        $row_raw    = array_values($keyboard)[$row];
        $column_raw = array_values($row_raw)[$column];

        self::assertSame($value, $column_raw[$property], $message);
    }

    public static function assertRowButtonPropertiesEqual(array $values, $property, $keyboard, $row, $message = '')
    {
        $column = 0;
        foreach ($values as $value) {
            self::assertButtonPropertiesEqual($value, $property, $keyboard, $row, $column++, $message);
        }
        self::assertCount(count(array_values($keyboard)[$row]), $values);
    }

    public static function assertAllButtonPropertiesEqual(array $all_values, $property, $keyboard, $message = '')
    {
        $row = 0;
        foreach ($all_values as $values) {
            self::assertRowButtonPropertiesEqual($values, $property, $keyboard, $row++, $message);
        }
        self::assertCount(count($keyboard), $all_values);
    }
}