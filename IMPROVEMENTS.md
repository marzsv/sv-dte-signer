# Mejoras Identificadas - SV DTE Signer

Este documento lista las áreas de mejora identificadas en la librería, organizadas por prioridad.

---

## Estado de Implementación

| # | Mejora | Prioridad | Estado |
|---|--------|-----------|--------|
| 1 | [Cobertura de tests incompleta](#1-cobertura-de-tests-incompleta) | 🔴 Alta | ⬜ Pendiente |
| 2 | [Limpieza de memoria inefectiva](#2-limpieza-de-memoria-inefectiva) | 🔴 Alta | ⬜ Pendiente |
| 3 | [Renombrar passwordPri a nombre descriptivo](#3-renombrar-passwordpri-a-nombre-descriptivo) | 🔴 Alta | ⬜ Pendiente |
| 4 | [Código duplicado en processPrivateKey](#4-código-duplicado-en-processprivatekey) | 🟠 Media | ⬜ Pendiente |
| 5 | [Validación de NIT inconsistente](#5-validación-de-nit-inconsistente) | 🟠 Media | ⬜ Pendiente |
| 6 | [Validación de password vacía](#6-validación-de-password-vacía) | 🟠 Media | ⬜ Pendiente |
| 7 | [Ausencia de interfaces](#7-ausencia-de-interfaces) | 🟠 Media | ⬜ Pendiente |
| 8 | [Re-envolvimiento de excepciones](#8-re-envolvimiento-de-excepciones) | 🟡 Baja | ⬜ Pendiente |
| 9 | [Supresión de warnings XML ausente](#9-supresión-de-warnings-xml-ausente) | 🟡 Baja | ⬜ Pendiente |
| 10 | [OpenSSL error string sin verificar](#10-openssl-error-string-sin-verificar) | 🟡 Baja | ⬜ Pendiente |
| 11 | [Constantes duplicadas](#11-constantes-duplicadas) | 🟡 Baja | ⬜ Pendiente |
| 12 | [Falta de tests de integración](#12-falta-de-tests-de-integración) | 🟡 Baja | ⬜ Pendiente |
| 13 | [Sin sistema de logging](#13-sin-sistema-de-logging) | 🟡 Baja | ⬜ Pendiente |
| 14 | [Sin verificación de expiración de certificados](#14-sin-verificación-de-expiración-de-certificados) | 🟡 Baja | ⬜ Pendiente |

---

## Detalle de Mejoras

### 1. Cobertura de Tests Incompleta

**Prioridad:** 🔴 Alta
**Estado:** ⬜ Pendiente

**Descripción:**
Solo hay 4 archivos de test pero 13 clases en `src/`. Varias clases críticas no tienen tests unitarios.

**Clases sin tests:**

| Clase | Archivo | Criticidad |
|-------|---------|------------|
| `DteSigner` | `src/DteSigner.php` | **Alta** - Clase principal de firma |
| `CertificateLoader` | `src/Certificate/CertificateLoader.php` | **Alta** - Carga y valida certificados |
| `CertificateParser` | `src/Certificate/CertificateParser.php` | **Alta** - Parsea XML de MH |
| `JwsSigner` | `src/Signing/JwsSigner.php` | **Alta** - Crea firmas JWS |
| `CertificateValidator` | `src/Validators/CertificateValidator.php` | Media |
| `DteSignerException` | `src/Exceptions/DteSignerException.php` | Baja |
| `ValidationException` | `src/Exceptions/ValidationException.php` | Baja |
| `CertificateException` | `src/Exceptions/CertificateException.php` | Baja |
| `VerificationException` | `src/Exceptions/VerificationException.php` | Baja |

**Solución:**
Crear tests unitarios para cada clase faltante, priorizando las de alta criticidad.

---

### 2. Limpieza de Memoria Inefectiva

**Prioridad:** 🔴 Alta (Seguridad)
**Estado:** ⬜ Pendiente

**Descripción:**
En `src/DteSigner.php:136-145`, el método `clearSensitiveData()` recibe el array por valor (copia), no por referencia, por lo que el `unset()` no afecta la variable original.

**Código actual:**
```php
private function clearSensitiveData(array $data): void  // ← Recibe copia
{
    if (isset($data['passwordPri'])) {
        unset($data['passwordPri']);  // ← Solo limpia la copia local
    }

    if (isset($data['passwordPub'])) {
        unset($data['passwordPub']);
    }
}
```

**Problema:**
La contraseña permanece en memoria en la variable `$requestData` original después de llamar a este método.

**Solución:**
Cambiar el parámetro a referencia `array &$data` o implementar una estrategia de limpieza más robusta.

---

### 3. Renombrar passwordPri a Nombre Descriptivo

**Prioridad:** 🔴 Alta
**Estado:** ⬜ Pendiente

**Descripción:**
El nombre `passwordPri` no es descriptivo ni sigue convenciones de nomenclatura claras. Es una abreviación confusa que no indica claramente su propósito.

**Ubicaciones afectadas:**
- `src/DteSigner.php:56, 62, 138`
- `src/Validators/RequestValidator.php:26, 39`
- `examples/basic_usage.php`
- `examples/sample_dte_request.json`
- `README.md` (documentación)

**Nombres actuales vs propuestos:**

| Actual | Propuesto | Razón |
|--------|-----------|-------|
| `passwordPri` | `certificatePassword` | Describe claramente que es la contraseña del certificado |
| `passwordPub` | `publicKeyPassword` | (si se usa) Contraseña de la clave pública |

**Solución:**
Renombrar `passwordPri` a `certificatePassword` en todo el código, ejemplos y documentación.

---

### 4. Código Duplicado en processPrivateKey

**Prioridad:** 🟠 Media
**Estado:** ⬜ Pendiente

**Descripción:**
La función `processPrivateKey()` está implementada de forma casi idéntica en dos lugares:

1. `src/Signing/JwsSigner.php:58-98`
2. `src/Certificate/CertificateLoader.php:138-156`

Ambas realizan la misma conversión de formato base64/DER a PEM.

**Solución:**
Extraer la lógica común a una clase utilitaria `KeyFormatter` o similar en `src/Utils/`.

---

### 5. Validación de NIT Inconsistente

**Prioridad:** 🟠 Media
**Estado:** ⬜ Pendiente

**Descripción:**
La validación del NIT se realiza de forma diferente en distintas partes del código:

| Ubicación | Validación | Acepta "ABCDEFGHIJKLMN"? |
|-----------|------------|--------------------------|
| `RequestValidator.php:14` | `^\d{14}$` (regex numérico) | ❌ No |
| `DteVerifier.php:55-60` | Solo `strlen($nit) !== 14` | ✅ Sí (bug) |

**Código en DteVerifier.php:**
```php
if (empty($nit) || strlen($nit) !== 14) {
    throw new VerificationException(
        'Invalid NIT format',
        ['NIT must be exactly 14 characters long']
    );
}
```

**Solución:**
Usar el mismo patrón de validación `^\d{14}$` en ambos lugares, idealmente centralizando la validación en `RequestValidator` o creando un `NitValidator`.

---

### 6. Validación de Password Vacía

**Prioridad:** 🟠 Media
**Estado:** ⬜ Pendiente

**Descripción:**
En `src/Validators/CertificateValidator.php:62-72`, el método `validatePassword()` existe pero no realiza ninguna validación real.

**Código actual:**
```php
private function validatePassword(array $certificateData, string $providedPassword): void
{
    if (!isset($certificateData['passwordHash'])) {
        throw CertificateException::invalidCertificate('Password hash is missing');
    }

    // For MH certificates, we skip password hash validation since they don't store password hashes
    // The actual password validation will happen during JWS signing when OpenSSL decrypts the private key
    // This ensures that incorrect passwords will be caught during the signing process
    return;  // ← No hace nada
}
```

**Solución:**
Documentar claramente por qué no se valida aquí, o eliminar el método si no es necesario. Considerar si hay alguna validación que sí se pueda hacer.

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
**Estado:** ⬜ Pendiente

**Descripción:**
En `src/Signing/JwsSigner.php:47-52`, el catch genérico re-envuelve excepciones que ya son `DteSignerException`, perdiendo el código de error original.

**Código actual:**
```php
try {
    if (empty($privateKey)) {
        throw new DteSignerException('Private key cannot be empty', 'COD_815');
    }
    // ...
} catch (\Exception $e) {
    throw new DteSignerException(
        'Failed to sign DTE: ' . $e->getMessage(),
        'COD_815'  // ← Siempre COD_815, aunque la original fuera diferente
    );
}
```

**Solución:**
Verificar si la excepción ya es `DteSignerException` antes de re-envolverla:
```php
} catch (DteSignerException $e) {
    throw $e;
} catch (\Exception $e) {
    throw new DteSignerException('Failed to sign DTE: ' . $e->getMessage(), 'COD_815');
}
```

---

### 9. Supresión de Warnings XML Ausente

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
En `src/Certificate/CertificateParser.php:26`, `loadXML()` puede generar warnings de PHP para XML malformado antes de que se lance la excepción.

**Código actual:**
```php
if (!$document->loadXML($xmlContent)) {
    throw CertificateException::invalidCertificate('Invalid XML format');
}
```

**Solución:**
Usar `libxml_use_internal_errors(true)` para capturar errores de forma limpia:
```php
libxml_use_internal_errors(true);
if (!$document->loadXML($xmlContent)) {
    $errors = libxml_get_errors();
    libxml_clear_errors();
    throw CertificateException::invalidCertificate('Invalid XML format');
}
```

---

### 10. OpenSSL Error String Sin Verificar

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
En varios lugares se concatena `openssl_error_string()` directamente, pero esta función devuelve `false` cuando no hay errores pendientes.

**Ubicaciones:**
- `src/Certificate/CertificateLoader.php:101, 110`
- `src/Signing/JwsSigner.php:84, 93`

**Código actual:**
```php
'Cannot load private key: ' . openssl_error_string()  // Puede ser false
```

**Solución:**
Verificar el retorno antes de concatenar:
```php
$error = openssl_error_string();
$message = 'Cannot load private key' . ($error ? ': ' . $error : '');
```

---

### 11. Constantes Duplicadas

**Prioridad:** 🟡 Baja
**Estado:** ⬜ Pendiente

**Descripción:**
La constante `DEFAULT_CERTIFICATE_DIRECTORY` está definida en múltiples clases:

```php
// DteSigner.php:22
private const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';

// DteVerifier.php:22
private const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';
```

**Solución:**
Mover a una clase de configuración centralizada o crear una constante compartida.

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
