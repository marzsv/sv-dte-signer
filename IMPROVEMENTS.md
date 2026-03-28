# Mejoras Identificadas - SV DTE Signer

Este documento lista las áreas de mejora identificadas en la librería, organizadas por prioridad.

---

## Estado de Implementación

### Mejoras Funcionales

| # | Mejora | Prioridad | Estado |
|---|--------|-----------|--------|
| 1 | [Cobertura de tests incompleta](#1-cobertura-de-tests-incompleta) | 🔴 Alta | ✅ Completado |
| 2 | [Limpieza de memoria inefectiva](#2-limpieza-de-memoria-inefectiva) | 🔴 Alta | ✅ Completado |
| 3 | [Renombrar passwordPri a nombre descriptivo](#3-renombrar-passwordpri-a-nombre-descriptivo) | 🔴 Alta | ✅ Completado |
| 4 | [Código duplicado en processPrivateKey](#4-código-duplicado-en-processprivatekey) | 🟠 Media | ✅ Completado |
| 5 | [Validación de NIT inconsistente](#5-validación-de-nit-inconsistente) | 🟠 Media | ✅ Completado |
| 6 | [Validación de password vacía](#6-validación-de-password-vacía) | 🟠 Media | ✅ Completado |
| 7 | [Ausencia de interfaces](#7-ausencia-de-interfaces) | 🟠 Media | ⬜ Pendiente |
| 8 | [Re-envolvimiento de excepciones](#8-re-envolvimiento-de-excepciones) | 🟡 Baja | ✅ Completado |
| 9 | [Supresión de warnings XML ausente](#9-supresión-de-warnings-xml-ausente) | 🟡 Baja | ✅ Completado |
| 10 | [OpenSSL error string sin verificar](#10-openssl-error-string-sin-verificar) | 🟡 Baja | ✅ Completado |
| 11 | [Constantes duplicadas](#11-constantes-duplicadas) | 🟡 Baja | ✅ Completado |
| 12 | [Falta de tests de integración](#12-falta-de-tests-de-integración) | 🟡 Baja | ⬜ Pendiente |
| 13 | [Sin sistema de logging](#13-sin-sistema-de-logging) | 🟡 Baja | ⬜ Pendiente |
| 14 | [Sin verificación de expiración de certificados](#14-sin-verificación-de-expiración-de-certificados) | 🟡 Baja | ⬜ Pendiente |

### Mejoras de Performance

| # | Mejora | Impacto | Estado |
|---|--------|---------|--------|
| P1 | [Caché de certificados parseados](#p1-caché-de-certificados-parseados) | ⬆️ Alto | ⬜ Pendiente |
| P2 | [Caché de claves procesadas](#p2-caché-de-claves-procesadas) | ⬆️ Medio | ⬜ Pendiente |
| P3 | [Reutilización de DOMXPath](#p3-reutilización-de-domxpath) | ⬆️ Bajo | ⬜ Pendiente |
| P4 | [Reducción de llamadas FS](#p4-reducción-de-llamadas-al-sistema-de-archivos) | ⬆️ Bajo | ⬜ Pendiente |
| P5 | [JSON_THROW_ON_ERROR](#p5-uso-de-json_throw_on_error) | ⬆️ Muy bajo | ⬜ Pendiente |
| P6 | [Eliminar hash no usado](#p6-eliminación-de-hash-placeholder-no-usado) | ⬆️ Bajo | ⬜ Pendiente |

---

## Detalle de Mejoras

### 1. Cobertura de Tests Incompleta

**Prioridad:** 🔴 Alta
**Estado:** ✅ Completado

**Descripción:**
Solo había 4 archivos de test pero 13 clases en `src/`. Varias clases críticas no tenían tests unitarios.

**Solución implementada:**
Se crearon tests unitarios para todas las clases críticas:

| Clase | Test | Tests agregados |
|-------|------|-----------------|
| `DteSigner` | `DteSignerTest.php` | 10 tests |
| `JwsSigner` | `JwsSignerTest.php` | 10 tests |
| `CertificateLoader` | `CertificateLoaderTest.php` | 8 tests |
| `CertificateParser` | `CertificateParserTest.php` | 12 tests |
| `CertificateValidator` | `CertificateValidatorTest.php` | 10 tests |

**Resultado:**
- Tests anteriores: 37 tests, 89 assertions
- Tests nuevos: **87 tests, 196 assertions**
- Incremento: +50 tests (+135%), +107 assertions (+120%)

---

### 2. Limpieza de Memoria Inefectiva

**Prioridad:** 🔴 Alta (Seguridad)
**Estado:** ✅ Completado

**Descripción:**
En `src/DteSigner.php:136-145`, el método `clearSensitiveData()` recibía el array por valor (copia), no por referencia, por lo que el `unset()` no afectaba la variable original.

**Solución implementada:**
- Cambió el parámetro a referencia `array &$data`
- Se sobrescriben los valores con null bytes antes de hacer unset para minimizar el tiempo que las contraseñas permanecen en memoria

```php
private function clearSensitiveData(array &$data): void
{
    $sensitiveFields = ['privateKeyPassword'];

    foreach ($sensitiveFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = str_repeat("\0", strlen((string) $data[$field]));
            unset($data[$field]);
        }
    }
}
```

---

### 3. Renombrar passwordPri a Nombre Descriptivo

**Prioridad:** 🔴 Alta
**Estado:** ✅ Completado

**Descripción:**
El nombre `passwordPri` no era descriptivo ni seguía convenciones de nomenclatura claras.

**Solución implementada:**
Se renombró la key a un nombre técnicamente preciso:

| Anterior | Nuevo | Razón |
|----------|-------|-------|
| `passwordPri` | `privateKeyPassword` | Describe precisamente que es la contraseña para desencriptar la clave privada |

**Archivos modificados:**
- `src/DteSigner.php`
- `src/Validators/RequestValidator.php`
- `tests/Unit/RequestValidatorTest.php`
- `examples/basic_usage.php`
- `examples/verification_usage.php`
- `examples/error_handling.php`
- `examples/sample_dte_request.json`
- `README.md`

**Nota:** Este es un breaking change para usuarios existentes. También se eliminó `publicKeyPassword` ya que no se usaba.

---

### 4. Código Duplicado en processPrivateKey

**Prioridad:** 🟠 Media
**Estado:** ✅ Completado

**Descripción:**
La función `processPrivateKey()` estaba implementada de forma casi idéntica en dos lugares:

1. `src/Signing/JwsSigner.php:58-98`
2. `src/Certificate/CertificateLoader.php:138-156`

Ambas realizaban la misma conversión de formato base64/DER a PEM.

**Solución implementada:**
Se creó una clase utilitaria `KeyFormatter` en `src/Utils/KeyFormatter.php` con métodos estáticos:

```php
class KeyFormatter
{
    public static function toPem(string $privateKey): string
    public static function toPemDecrypted(string $privateKey, ?string $password = null): string
    public static function isPemFormat(string $key): bool
}
```

**Archivos modificados:**
- `src/Utils/KeyFormatter.php` (nuevo)
- `src/Signing/JwsSigner.php` (usa `KeyFormatter::toPemDecrypted()`)
- `src/Certificate/CertificateLoader.php` (usa `KeyFormatter::toPem()`)
- `tests/Unit/KeyFormatterTest.php` (nuevo, 10 tests)

---

### 5. Validación de NIT Inconsistente

**Prioridad:** 🟠 Media
**Estado:** ✅ Completado

**Descripción:**
La validación del NIT se realizaba de forma diferente en distintas partes del código:

| Ubicación | Validación | Acepta "ABCDEFGHIJKLMN"? |
|-----------|------------|--------------------------|
| `RequestValidator.php:14` | `^\d{14}$` (regex numérico) | ❌ No |
| `DteVerifier.php:55-60` | Solo `strlen($nit) !== 14` | ✅ Sí (bug) |

**Solución implementada:**
Se creó una clase centralizada `NitValidator` en `src/Validators/NitValidator.php`:

```php
class NitValidator
{
    public static function isValid(string $nit): bool
    public static function validate(string $nit): array
    public static function getExpectedLength(): int
}
```

Ahora tanto `RequestValidator` como `DteVerifier` usan `NitValidator::validate()` para validación consistente.

**Archivos modificados:**
- `src/Validators/NitValidator.php` (nuevo)
- `src/Validators/RequestValidator.php` (usa `NitValidator::validate()`)
- `src/DteVerifier.php` (usa `NitValidator::validate()`)
- `tests/Unit/NitValidatorTest.php` (nuevo, 14 tests)

---

### 6. Validación de Password Vacía

**Prioridad:** 🟠 Media
**Estado:** ✅ Completado

**Descripción:**
En `src/Validators/CertificateValidator.php:62-72`, el método `validatePassword()` existía pero no realizaba ninguna validación real.

**Solución implementada:**
Se eliminó el método `validatePassword()` y se documentó claramente en la clase por qué la validación de password no ocurre aquí:

```php
/**
 * Validates MH certificates according to DTE specifications
 *
 * Note: Password validation is intentionally NOT performed here.
 * MH certificates don't store password hashes. The actual password
 * validation happens during JWS signing when OpenSSL decrypts the
 * private key - if the password is wrong, decryption will fail.
 */
class CertificateValidator
```

**Archivos modificados:**
- `src/Validators/CertificateValidator.php` (documentación y limpieza)
- `tests/Unit/CertificateValidatorTest.php` (test actualizado)

---

### 7. Ausencia de Interfaces

**Prioridad:** 🟠 Media
**Estado:** ⬜ Pendiente

**Descripción:**
Las dependencias se inyectan usando clases concretas en lugar de interfaces, lo que dificulta el testing y la extensibilidad.

**Código actual:**
```php
public function __construct(
    string $certificateDirectory = self::DEFAULT_CERTIFICATE_DIRECTORY,
    ?RequestValidator $requestValidator = null,     // ← Clase concreta
    ?CertificateLoader $certificateLoader = null,   // ← Clase concreta
    ?JwsSigner $jwsSigner = null                    // ← Clase concreta
)
```

**Interfaces propuestas:**
- `RequestValidatorInterface`
- `CertificateLoaderInterface`
- `JwsSignerInterface`
- `JwsVerifierInterface`

**Solución:**
Crear interfaces para las dependencias principales y actualizar las clases para que las implementen.

---

### 8. Re-envolvimiento de Excepciones

**Prioridad:** 🟡 Baja
**Estado:** ✅ Completado

**Descripción:**
En `src/Signing/JwsSigner.php:47-52`, el catch genérico re-envolvía excepciones que ya eran `DteSignerException`, perdiendo el código de error original.

**Código anterior:**
```php
} catch (\Exception $e) {
    throw new DteSignerException(
        'Failed to sign DTE: ' . $e->getMessage(),
        'COD_815'  // ← Siempre COD_815, aunque la original fuera diferente
    );
}
```

**Solución implementada:**
Se agregó un catch específico para `DteSignerException` antes del catch genérico:

```php
} catch (DteSignerException $e) {
    throw $e;
} catch (\Exception $e) {
    throw new DteSignerException('Failed to sign DTE: ' . $e->getMessage(), 'COD_815');
}
```

**Archivos modificados:**
- `src/Signing/JwsSigner.php`
- `src/Certificate/CertificateLoader.php` (mismo patrón aplicado)

---

### 9. Supresión de Warnings XML Ausente

**Prioridad:** 🟡 Baja
**Estado:** ✅ Completado

**Descripción:**
En `src/Certificate/CertificateParser.php:26`, `loadXML()` podía generar warnings de PHP para XML malformado antes de que se lanzara la excepción.

**Solución implementada:**
Se usa `libxml_use_internal_errors(true)` para capturar errores de forma limpia, incluyendo el primer mensaje de error en la excepción:

```php
$previousErrorState = libxml_use_internal_errors(true);
try {
    if (!$document->loadXML($xmlContent)) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $errorMessage = 'Invalid XML format';
        if (!empty($errors)) {
            $errorMessage .= ': ' . $errors[0]->message;
        }
        throw CertificateException::invalidCertificate(trim($errorMessage));
    }
    libxml_clear_errors();
    // ...
} finally {
    libxml_use_internal_errors($previousErrorState);
}
```

**Archivos modificados:**
- `src/Certificate/CertificateParser.php`

---

### 10. OpenSSL Error String Sin Verificar

**Prioridad:** 🟡 Baja
**Estado:** ✅ Completado

**Descripción:**
En varios lugares se concatenaba `openssl_error_string()` directamente, pero esta función devuelve `false` cuando no hay errores pendientes.

**Solución implementada:**
El manejo de errores OpenSSL se centralizó en `KeyFormatter` (mejora #4), donde se verifica el retorno:

```php
$error = openssl_error_string();
throw new DteSignerException(
    'Cannot decrypt private key with provided password' . ($error ? ': ' . $error : ''),
    'COD_814'
);
```

También se aplicó el mismo patrón en `CertificateLoader::extractPublicKeyFromPrivateKey()`.

**Archivos modificados:**
- `src/Utils/KeyFormatter.php` (centralizado)
- `src/Certificate/CertificateLoader.php`

---

### 11. Constantes Duplicadas

**Prioridad:** 🟡 Baja
**Estado:** ✅ Completado

**Descripción:**
La constante `DEFAULT_CERTIFICATE_DIRECTORY` estaba definida en múltiples clases.

**Solución implementada:**
Se creó una clase `Config` centralizada para constantes de configuración:

```php
// src/Config.php
final class Config
{
    public const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';

    private function __construct() {}
}
```

Ahora ambas clases usan `Config::DEFAULT_CERTIFICATE_DIRECTORY`.

**Archivos modificados:**
- `src/Config.php` (nuevo)
- `src/DteSigner.php`
- `src/DteVerifier.php`

---

### 12. Falta de Tests de Integración

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
Solo existen tests unitarios con mocks. No hay tests que:
- Prueben el flujo completo de firma → verificación
- Usen certificados mock reales generados
- Validen compatibilidad con el formato MH real

**Solución:**
Crear un directorio `tests/Integration/` con tests que:
1. Generen un certificado mock
2. Firmen un DTE
3. Verifiquen la firma
4. Extraigan el payload

---

### 13. Sin Sistema de Logging

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
No hay ningún mecanismo de logging en la librería. Para uso en producción sería útil poder registrar:
- Intentos de firma (sin datos sensibles)
- Certificados no encontrados
- Errores de validación
- Tiempos de operación

**Solución:**
Agregar soporte opcional para PSR-3 `LoggerInterface`:
```php
public function __construct(
    string $certificateDirectory = 'certificates',
    ?LoggerInterface $logger = null
)
```

---

### 14. Sin Verificación de Expiración de Certificados

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
El `CertificateParser` no extrae ni valida fechas de expiración del certificado. Un certificado expirado sería aceptado sin advertencia.

**Solución:**
Extraer fechas de validez del certificado (si están disponibles en el formato MH) y opcionalmente validarlas o al menos incluirlas en la respuesta.

---

## Mejoras de Performance

Las siguientes mejoras están orientadas a optimizar el rendimiento de la librería para escenarios de alto volumen.

### P1. Caché de Certificados Parseados

**Prioridad:** 🔴 Alta (para alto volumen)
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Alto

**Problema:**
Cada llamada a `sign()` lee el archivo del certificado desde disco y lo parsea con DOMDocument. Para operaciones repetidas con el mismo NIT, esto es ineficiente.

**Código actual:**
```php
// CertificateLoader::loadCertificate() - se ejecuta en cada firma
$xmlContent = file_get_contents($certificateFile);  // I/O disco
$certificateData = $this->parser->parse($xmlContent);  // Parsing XML
```

**Solución propuesta:**
Implementar caché en memoria con TTL configurable:

```php
class CertificateLoader
{
    /** @var array<string, array{data: array, expires: int}> */
    private array $cache = [];
    private int $cacheTtl;

    public function __construct(
        string $certificateDirectory,
        int $cacheTtlSeconds = 300,  // 5 minutos por defecto
        // ...
    ) {
        $this->cacheTtl = $cacheTtlSeconds;
    }

    public function loadCertificate(string $nit, string $password): array
    {
        $cacheKey = $nit;

        if ($this->isCacheValid($cacheKey)) {
            return $this->cache[$cacheKey]['data'];
        }

        // ... cargar y parsear ...

        $this->cache[$cacheKey] = [
            'data' => $certificateData,
            'expires' => time() + $this->cacheTtl
        ];

        return $certificateData;
    }
}
```

**Benchmark estimado:**
- Sin caché: ~5-10ms por firma (I/O + XML parsing)
- Con caché: ~0.1ms por firma (solo lectura de array)

---

### P2. Caché de Claves Procesadas

**Prioridad:** 🟠 Media
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Medio

**Problema:**
`KeyFormatter::toPemDecrypted()` realiza operaciones OpenSSL costosas en cada llamada, incluso cuando la misma clave se usa repetidamente.

**Código actual:**
```php
// Se ejecuta en cada firma
$processedKey = KeyFormatter::toPemDecrypted($privateKey, $password);
// Internamente: openssl_pkey_get_private() + openssl_pkey_export()
```

**Solución propuesta:**
Caché estático con hash de la clave como key:

```php
class KeyFormatter
{
    /** @var array<string, string> */
    private static array $keyCache = [];

    public static function toPemDecrypted(string $privateKey, ?string $password = null): string
    {
        $cacheKey = hash('sha256', $privateKey . ($password ?? ''));

        if (isset(self::$keyCache[$cacheKey])) {
            return self::$keyCache[$cacheKey];
        }

        $pemKey = self::toPem($privateKey);
        // ... procesar ...

        self::$keyCache[$cacheKey] = $decryptedPem;
        return $decryptedPem;
    }

    public static function clearCache(): void
    {
        self::$keyCache = [];
    }
}
```

**Consideraciones de seguridad:**
- Las claves permanecen en memoria durante la vida del proceso
- Proporcionar `clearCache()` para limpiar manualmente si es necesario
- En entornos de alta seguridad, puede ser preferible no cachear

---

### P3. Reutilización de DOMXPath

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Bajo

**Problema:**
En `CertificateParser`, se crean múltiples objetos `DOMXPath` para el mismo documento.

**Código actual:**
```php
private function parseMhCertificate(DOMDocument $document): array
{
    $xpath = new DOMXPath($document);  // Creado aquí
    // ...
}

private function extractValueFromPath(DOMDocument $document, string $xpath): ?string
{
    $xpathObj = new DOMXPath($document);  // Creado de nuevo
    // ...
}
```

**Solución propuesta:**
Pasar el objeto XPath como parámetro o almacenarlo temporalmente:

```php
private function parseMhCertificate(DOMDocument $document): array
{
    $xpath = new DOMXPath($document);

    // Usar el mismo xpath para todas las extracciones
    $privateKey = $this->extractValueWithXPath($xpath, '//privateKey/encodied');
    // ...
}

private function extractValueWithXPath(DOMXPath $xpath, string $expression): ?string
{
    $elements = $xpath->query($expression);
    return ($elements && $elements->length > 0)
        ? $elements->item(0)?->nodeValue
        : null;
}
```

---

### P4. Reducción de Llamadas al Sistema de Archivos

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Bajo

**Problema:**
Se realizan dos llamadas al filesystem: `file_exists()` y `file_get_contents()`.

**Código actual:**
```php
if (!file_exists($certificateFile)) {
    throw CertificateException::certificateNotFound($nit);
}

$xmlContent = file_get_contents($certificateFile);

if ($xmlContent === false) {
    throw CertificateException::invalidCertificate('Could not read certificate file');
}
```

**Solución propuesta:**
Una sola llamada con manejo de errores:

```php
$xmlContent = @file_get_contents($certificateFile);

if ($xmlContent === false) {
    if (!file_exists($certificateFile)) {
        throw CertificateException::certificateNotFound($nit);
    }
    throw CertificateException::invalidCertificate('Could not read certificate file');
}
```

**Nota:** El `@` suprime warnings, pero el error se maneja correctamente después.

---

### P5. Uso de JSON_THROW_ON_ERROR

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Muy bajo

**Problema:**
Se usa el patrón antiguo de `json_decode()` + `json_last_error()`.

**Código actual:**
```php
$data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new ValidationException(
        'Invalid JSON in request file: ' . json_last_error_msg(),
        ['JSON parsing error']
    );
}
```

**Solución propuesta:**
Usar `JSON_THROW_ON_ERROR` (PHP 7.3+):

```php
try {
    $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException $e) {
    throw new ValidationException(
        'Invalid JSON in request file: ' . $e->getMessage(),
        ['JSON parsing error']
    );
}
```

**Beneficios:**
- Código más limpio y moderno
- Evita la necesidad de verificar `json_last_error()` global
- Mejor manejo de errores con excepciones

---

### P6. Eliminación de Hash Placeholder No Usado

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente
**Impacto estimado:** ⬆️ Bajo

**Problema:**
`CertificateParser::generatePlaceholderHash()` genera un hash SHA256 que nunca se usa para validación.

**Código actual:**
```php
// Se genera pero nunca se valida
$passwordHash = $this->generatePlaceholderHash($document);

return [
    // ...
    'passwordHash' => $passwordHash  // No se usa
];
```

**Solución propuesta:**
Eliminar la generación del hash si no se necesita:

```php
return [
    'activo' => $activoBool ? 'true' : 'false',
    'verificado' => $verificadoBool ? 'true' : 'false',
    'privateKey' => $privateKey,
    // passwordHash eliminado
];
```

**Nota:** Esto es un breaking change menor si alguien depende de este campo.

---

### Resumen de Mejoras de Performance

| # | Mejora | Impacto | Esfuerzo | Prioridad |
|---|--------|---------|----------|-----------|
| P1 | Caché de certificados | ⬆️ Alto | Medio | 🔴 Alta |
| P2 | Caché de claves | ⬆️ Medio | Bajo | 🟠 Media |
| P3 | Reutilizar DOMXPath | ⬆️ Bajo | Bajo | 🟡 Baja |
| P4 | Reducir llamadas FS | ⬆️ Bajo | Bajo | 🟡 Baja |
| P5 | JSON_THROW_ON_ERROR | ⬆️ Muy bajo | Bajo | 🟡 Baja |
| P6 | Eliminar hash no usado | ⬆️ Bajo | Bajo | 🟡 Baja |

### Recomendación para Alto Volumen

Para escenarios de alto volumen (>100 firmas/segundo), implementar en este orden:

1. **P1** - Caché de certificados (mayor impacto)
2. **P2** - Caché de claves (complementa P1)
3. Resto opcional según necesidad

---

## Notas de Implementación

### Orden Sugerido de Implementación

1. **Fase 1 - Crítico:**
   - #3 Renombrar passwordPri (breaking change, mejor hacerlo primero)
   - #2 Limpieza de memoria

2. **Fase 2 - Tests:**
   - #1 Tests para clases críticas
   - #12 Tests de integración

3. **Fase 3 - Calidad de Código:**
   - #4 Eliminar código duplicado
   - #5 Validación de NIT consistente
   - #8 Re-envolvimiento de excepciones

4. **Fase 4 - Mejoras Opcionales:**
   - #7 Interfaces
   - #9, #10, #11 Pequeñas mejoras
   - #13, #14 Funcionalidades adicionales

---

## Historial de Cambios

| Fecha | Cambio |
|-------|--------|
| 2025-11-30 | Documento inicial creado |
| 2025-11-30 | ✅ #3 Completado: Renombrado `passwordPri` → `privateKeyPassword` |
| 2025-11-30 | ✅ #2 Completado: Arreglada limpieza de memoria con referencia y sobrescritura |
| 2025-11-30 | ✅ #1 Completado: Tests para clases críticas (87 tests, 196 assertions) |
| 2025-12-01 | ✅ #4 Completado: Extraída lógica duplicada a `KeyFormatter` |
| 2025-12-01 | ✅ #5 Completado: Centralizada validación NIT en `NitValidator` |
| 2025-12-01 | ✅ #8 Completado: Corregido re-envolvimiento de excepciones |
| 2025-12-01 | ✅ #6 Completado: Limpieza de validación de password no usada |
| 2025-12-01 | ✅ #9 Completado: Supresión de warnings XML con libxml |
| 2025-12-01 | ✅ #10 Completado: Verificación de openssl_error_string |
| 2025-12-01 | ✅ #11 Completado: Centralizada constante en `Config` |
| 2025-12-01 | Tests actualizados: 108 tests, 221 assertions |
| 2025-12-01 | 📊 Documentadas 6 mejoras de performance (P1-P6) |
