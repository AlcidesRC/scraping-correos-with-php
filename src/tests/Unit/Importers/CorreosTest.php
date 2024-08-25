<?php

declare(strict_types=1);

namespace Tests\Unit\Importers;

use App\CsvHandler;
use App\Importers\Correos;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SlopeIt\ClockMock\ClockMock;

#[CoversClass(Correos::class)]
#[CoversClass(CsvHandler::class)]
final class CorreosTest extends TestCase
{
    private const PATH_OUTPUT = '/output/';
    private const CSV_HEADERS = ['Country', 'Postal Code', 'City', 'Province', 'Region', 'Latitude', 'Longitude'];

    protected function setUp(): void
    {
        ClockMock::freeze(new \DateTime('2024-01-01 00:00:00'));
    }

    protected function tearDown(): void
    {
        ClockMock::reset();
    }

    private static function emptyOutputFolder(): void
    {
        $files = glob(strtr('{PATH}/province-*.csv', ['{PATH}' => self::PATH_OUTPUT]));

        if ($files === false) {
            return;
        }

        array_walk($files, fn(string $filename) => unlink($filename));
    }

    /**
     * @return array<int, string>
     * @throws \Throwable
     */
    private static function processProvince(int $provinceId): array
    {
        $filename = strtr('{PATH}/province-{PROVINCE}.csv', [
            '{PATH}' => rtrim(self::PATH_OUTPUT, '/'),
            '{PROVINCE}' => str_pad((string) $provinceId, 2, '0', STR_PAD_LEFT),
        ]);

        if (file_exists($filename) && is_readable($filename)) {
            $data = [];
            foreach (CsvHandler::read($filename) as $row) {
                $data[] = $row;
            }

            // Ignore headers
            array_shift($data);
        } else {
            $data = (new Correos())->getProvincePostalCodes($provinceId);
            CsvHandler::write($filename, $data, self::CSV_HEADERS);
        }

        return $data;
    }

    #[Test]
    #[Group('small')]
    public function checkExceptionIsRaisedWithWrongProvince(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(Correos::getExceptionMessage(PHP_INT_MAX));

        (new Correos())->getProvincePostalCodes(PHP_INT_MAX);
    }

    #[Test]
    #[DataProvider('dataProviderForScrapeProvince')]
    #[Group('large')]
    public function scrapeProvince(int $provinceId, int $expectedSize): void
    {
        $data = self::processProvince($provinceId);

        self::assertIsArray($data);
        self::assertCount($expectedSize, $data);
    }

    /**
     * @param array<int, float|string> $expectedFirstRow
     */
    #[Test]
    #[DataProvider('dataProviderForValidateFirstPostalCode')]
    #[Group('small')]
    public function validateFirstPostalCodeFromSpecificProvinces(int $provinceId, array $expectedFirstRow): void
    {
        $data = self::processProvince($provinceId);

        self::assertIsArray($data);
        self::assertEquals($expectedFirstRow, $data[0]);
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function dataProviderForScrapeProvince(): array
    {
        //self::emptyOutputFolder();

        return [
            'ARABA/ÁLAVA' => [1, 78],
            'ALBACETE' => [2, 128],
            'ALICANTE/ALACANT' => [3, 208],
            'ALMERÍA' => [4, 165],
            'ÁVILA' => [5, 145],
            'BADAJOZ' => [6, 213],
            'BALEARS, ILLES' => [7, 161],
            'BARCELONA' => [8, 384],
            'BURGOS' => [9, 214],
            'CÁCERES' => [10, 243],
            'CÁDIZ' => [11, 114],
            'CASTELLÓN/CASTELLÓ' => [12, 132],
            'CIUDAD REAL' => [13, 127],
            'CÓRDOBA' => [14, 138],
            'CORUÑA, A' => [15, 378],
            'CUENCA' => [16, 180],
            'GIRONA' => [17, 245],
            'GRANADA' => [18, 200],
            'GUADALAJARA' => [19, 178],
            'GIPUZKOA' => [20, 106],
            'HUELVA' => [21, 103],
            'HUESCA' => [22, 272],
            'JAÉN' => [23, 172],
            'LEÓN' => [24, 415],
            'LLEIDA' => [25, 298],
            'RIOJA, LA' => [26, 129],
            'LUGO' => [27, 461],
            'MADRID' => [28, 296],
            'MÁLAGA' => [29, 160],
            'MURCIA' => [30, 206],
            'NAVARRA' => [31, 262],
            'OURENSE' => [32, 296],
            'ASTURIAS' => [33, 402],
            'PALENCIA' => [34, 129],
            'PALMAS, LAS' => [35, 144],
            'PONTEVEDRA' => [36, 379],
            'SALAMANCA' => [37, 274],
            'SANTA CRUZ DE TENERIFE' => [38, 216],
            'CANTABRIA' => [39, 211],
            'SEGOVIA' => [40, 202],
            'SEVILLA' => [41, 152],
            'SORIA' => [42, 96],
            'TARRAGONA' => [43, 208],
            'TERUEL' => [44, 208],
            'TOLEDO' => [45, 229],
            'VALENCIA/VALÈNCIA' => [46, 298],
            'VALLADOLID' => [47, 191],
            'BIZKAIA' => [48, 137],
            'ZAMORA' => [49, 297],
            'ZARAGOZA' => [50, 272],
            'CEUTA' => [51, 5],
            'MELILLA' => [52, 6],
        ];
    }

    /**
     * @return array<string, array<int, array<int, float|string>|int>>
     */
    public static function dataProviderForValidateFirstPostalCode(): array
    {
        // phpcs:disable
        return [
            'ARABA/ÁLAVA' => [1, ['ESP', '01001', 'Vitoria-Gasteiz', 'Araba/Álava', 'País Vasco', 42.8469215, -2.671688]],
            'ALBACETE'    => [2, ['ESP', '02001', 'Albacete', 'Albacete', 'Castilla-La Mancha', 38.9982696, -1.8498994]],
        ];
        // phpcs:enable
    }
}
