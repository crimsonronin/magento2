<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Module;

class ConflictCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider checkConflictWhenEnableModuleDataProvider
     * @param array $conflictReturnMap
     * @param array $enabledModules
     * @param string $moduleName
     * @param array $expected
     */
    public function testCheckConflictsWhenEnableModules(
        $conflictReturnMap,
        $enabledModules,
        $moduleName,
        $expected
    ) {
        $moduleListMock = $this->getMock('Magento\Framework\Module\ModuleList', [], [], '', false);
        $packageInfoMock = $this->getMock('Magento\Framework\Module\PackageInfo', [], [], '', false);
        $moduleListMock->expects($this->any())
            ->method('getNames')
            ->will($this->returnValue($enabledModules));
        $packageInfoMock->expects($this->any())
            ->method('getConflict')
            ->will($this->returnValueMap($conflictReturnMap));
        $packageInfoMock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.1'));
        $packageInfoFactoryMock = $this->getMock('Magento\Framework\Module\PackageInfoFactory', [], [], '', false);
        $packageInfoFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($packageInfoMock));
        $conflictChecker = new ConflictChecker($moduleListMock, $packageInfoFactoryMock);
        $this->assertEquals($expected, $conflictChecker->checkConflictsWhenEnableModules($moduleName));
    }

    /**
     * @return array
     */
    public function checkConflictWhenEnableModuleDataProvider()
    {
        return [
            [
                [['Vendor_A', ['Vendor_B' => '0.1']], ['Vendor_B', []]],
                ['Vendor_A'],
                ['Vendor_B'],
                ['Vendor_B' => ['Vendor_A conflicts with Vendor_B version 0.1 (current 0.1)']]
            ],
            [
                [['Vendor_A', ['Vendor_B' => '0.1']], ['Vendor_B', []]],
                [],
                ['Vendor_B'],
                ['Vendor_B' => []]
            ],
            [
                [['Vendor_B', ['Vendor_A' => '0.1']], ['Vendor_A', []]],
                ['Vendor_A'],
                ['Vendor_B'],
                ['Vendor_B' => ['Vendor_B conflicts with Vendor_A version 0.1 (current 0.1)']]
            ],
            [
                [['Vendor_B', ['Vendor_A' => '0.1']], ['Vendor_A', []]],
                [],
                ['Vendor_B'],
                ['Vendor_B' => []]
            ],
            [
                [['Vendor_A', []], ['Vendor_B', []]],
                ['Vendor_A'],
                ['Vendor_B'],
                ['Vendor_B' => []]
            ],
            [
                [['Vendor_A', []], ['Vendor_B', []], ['Vendor_C', []]],
                ['Vendor_A'],
                ['Vendor_B', 'Vendor_C'],
                ['Vendor_B' => [], 'Vendor_C' => []]
            ],
            [
                [['Vendor_A', ['Vendor_C' => '0.1']], ['Vendor_B', []], ['Vendor_C', []]],
                ['Vendor_A'],
                ['Vendor_B', 'Vendor_C'],
                ['Vendor_B' => [], 'Vendor_C' => ['Vendor_A conflicts with Vendor_C version 0.1 (current 0.1)']]
            ],
            [
                [['Vendor_A', []], ['Vendor_B', ['Vendor_C' => '0.1']], ['Vendor_C', []]],
                ['Vendor_A'],
                ['Vendor_B', 'Vendor_C'],
                ['Vendor_B' => ['Vendor_B conflicts with Vendor_C version 0.1 (current 0.1)'], 'Vendor_C' => ['Vendor_B conflicts with Vendor_C version 0.1 (current 0.1)']]
            ],
            [
                [['Vendor_A', ['Vendor_B' => '>=0.1']], ['Vendor_B', []]],
                ['Vendor_A'],
                ['Vendor_B'],
                ['Vendor_B' => ['Vendor_A conflicts with Vendor_B version >=0.1 (current 0.1)']]
            ],
            [
                [['Vendor_A', ['Vendor_B' => '~0.1']], ['Vendor_B', []]],
                ['Vendor_A'],
                ['Vendor_B'],
            ['Vendor_B' => ['Vendor_A conflicts with Vendor_B version ~0.1 (current 0.1)']]
            ],
        ];
    }

    public function testCheckConflictWhenEnableModuleDifferentVersion()
    {
        $moduleListMock = $this->getMock('Magento\Framework\Module\ModuleList', [], [], '', false);
        $packageInfoMock = $this->getMock('Magento\Framework\Module\PackageInfo', [], [], '', false);
        $moduleListMock->expects($this->any())
            ->method('getNames')
            ->will($this->returnValue(['Vendor_A', 'Vendor_B']));
        $packageInfoMock->expects($this->any())
            ->method('getConflict')
            ->will($this->returnValueMap([
                ['Vendor_A', []],
                ['Vendor_B', []],
                ['Vendor_C', ['Vendor_A' => '>=0.2,<0.3', 'Vendor_B' => '<0.4']]
            ]));
        $packageInfoMock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValueMap([['Vendor_A', '0.2'], ['Vendor_B', '0.4']]));
        $packageInfoFactoryMock = $this->getMock('Magento\Framework\Module\PackageInfoFactory', [], [], '', false);
        $packageInfoFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($packageInfoMock));
        $conflictChecker = new ConflictChecker($moduleListMock, $packageInfoFactoryMock);
        $this->assertEquals(
            ['Vendor_C' => ['Vendor_C conflicts with Vendor_A version >=0.2,<0.3 (current 0.2)']],
            $conflictChecker->checkConflictsWhenEnableModules(['Vendor_C'])
        );
    }
}
