# Document Requirement Standard Code Convention

This document outlines the standard coding system used for identifying document requirements in the PKKI ITERA system.

## Code Structure

Each document requirement is assigned a unique standard code using the following pattern:

```
ST{TYPE}-{NAME}-{SEQ}
```

Where:
- **ST**: Static prefix indicating "Standard"
- **{TYPE}**: Four-letter code representing the submission type
- **{NAME}**: Four-letter mnemonic derived from the requirement name
- **{SEQ}**: Three-digit sequential number (001-999)

## Type Codes

| Submission Type     | Type Code |
|---------------------|-----------|
| Patent              | PAT       |
| Brand               | BRD       |
| Haki (Copyright)    | HAK       |
| Industrial Design   | IND       |

## Name Mnemonics

The name mnemonic is derived from the requirement name using the following rules:
1. For single-word names: First 4 characters of the word, uppercase
2. For multi-word names: First letter of each word (up to 4 letters), uppercase
3. If the result is less than 4 characters, additional characters from the first word are used

## Examples

| Requirement                          | Standard Code    |
|--------------------------------------|------------------|
| Checklist Paten                      | STPAT-CHKL-001   |
| Formulir Deskripsi Paten             | STPAT-FDP-002    |
| Etiket/Label Merek                   | STBRD-ELM-001    |
| Surat Pernyataan Hak Cipta           | STHAK-SPHC-001   |
| Gambar Desain Industri               | STIND-GDI-001    |

## Usage Guidelines

1. Standard codes must be unique across the entire system
2. Codes should be displayed alongside the requirement name in the user interface
3. Codes must be used for document tracking, auditing, and reference
4. When creating new requirements, use the DocumentCodeGeneratorService to generate appropriate codes
5. Never modify existing standard codes once they have been assigned to documents

## Implementation

The standard code system is implemented in the following components:

1. Database schema: `standard_code` column in the `document_requirements` table
2. Model: `DocumentRequirement` includes `standard_code` as a fillable property
3. Service: `DocumentCodeGeneratorService` generates standard codes
4. Seeder: `DocumentRequirementSeeder` assigns standard codes to requirements

## Benefits

- Consistent identification of document requirements
- Easy reference in communications and documentation
- Simplified tracking and auditing
- Support for document versioning
- Reduced risk of duplicate or misidentified requirements
