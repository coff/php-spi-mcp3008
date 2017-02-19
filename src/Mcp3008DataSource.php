<?php

namespace Coff\Mcp3008;

use Coff\DataSource\SpiDataSource;

/**
 * MCP3008 - class for reading A/D converter output
 *
 * Raspberry Pi hookup variants
 *
 * Direct over +3v
 * ------
 * VDD     -> +3v
 * Vref    -> +3v
 * A_GND   -> GND
 * CLK     -> SPISCLK
 * D_out   -> SPIMISO
 * D_in    -> SPIMOSI
 * CS/SHDN -> SPICS_ (0 or 1) // depends on which one you want to use - set via $cableSelect
 * D_GND   -> GND
 *
 * Powered with external power source (+5V)
 * ----------------------------------------
 * VDD     -> +5v (since Vref = Vdd +/- 0.6V)
 * Vref    -> +5v
 * A_GND   -> EXT_GND
 * CLK     -> SPISCLK
 * D_out   -> SPIMISO
 * D_in    -> SPIMOSI
 * CS/SHDN -> SPICS_ (0 or 1) // depends on which one you want to use - set via $cableSelect
 * D_GND   -> GND
 *
 * EXT_GND - external source ground
 * Tried also hooking up two grounds together and it seems to work either.
 *
 * Remarks:
 * 1. I've tried to run these things over RPi +5v but it seems it's too noisy to
 *    produce reliable results.
 *
 * 2. I've also tried running it over +3v on Vdd and RPi's +5v on Vref but that
 *    didn't work due to Vref = Vdd +/- 0.6V.
 */
class Mcp3008DataSource extends SpiDataSource
{
    const
        CH0     = 0b00000000,
        CH1     = 0b00010000,
        CH2     = 0b00100000,
        CH3     = 0b00110000,
        CH4     = 0b01000000,
        CH5     = 0b01010000,
        CH6     = 0b01100000,
        CH7     = 0b01110000,

        MODE_SINGLE = 0b10000000,
        MODE_DIFF   = 0b00000000;

    /**
     * @var int $mode reading mode, see:
     *      https://cdn-shop.adafruit.com/datasheets/MCP3008.pdf
     *      page 19, table 5-2
     */
    protected $mode;

    /**
     * @var int $channel channel according to self::CH* constants' values
     */
    protected $channel;

    public function __construct($busNumber = 0, $cableSelect = 0, $speed = 10000, $mode = self::MODE_SINGLE)
    {
        parent::__construct($busNumber, $cableSelect, $speed);
        $this->setMode($mode);
    }

    /**
     * Sets channel via number or constant
     * @param int $channel self::CH* constant or int 1-8
     *
     * @return $this
     */
    public function setChannel($channel = self::CH0) {
        if ($channel <= 8 && $channel > 0) {
            $channel = constant('self::CH' . ($channel - 1));
        }
        $this->channel = $channel;

        return $this;
    }

    /**
     * Returns channel
     *
     * @return int $channel value according to self::CH* constants
     */
    public function getChannel() {
        return $this->channel;
    }

    /**
     * Sets mode (SINGLE or DIFF). MODE_DIFF allows reading negative voltage
     * values - see MCP3008 datasheet.
     * @param int $mode
     *
     * @return $this
     */
    public function setMode($mode = self::MODE_SINGLE) {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Returns MCP3008 working mode
     * @return int
     */
    public function getMode() {
        return $this->mode;
    }

    /**
     * Initializes communications with MCP3008
     *
     * @return $this
     */
    public function init() {
        /**
         * SPI library: https://github.com/frak/php_spi
         */
        $this->spi = new \Spi(
            $this->busNumber, // bus number (always 0 on RPi)
            $this->cableSelect, // chip select CS (0 or 1)
            array (
                'mode' => SPI_MODE_0,
                'bits' => 8,
                'speed' => $this->speed, // min. 10KHz (10000)
                'delay' => 100000,
            )
        );

        return $this;
    }

    /**
     * Reads value from given channel
     *
     * @return $this;
     */
    public function update() {
        $x = $this->spi->transfer(array(1, $this->mode + $this->channel, 0));
        $this->value = ($x[1] << 8) + $x[2];

        return $this;
    }

}
