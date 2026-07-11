# Graph Report - .  (2026-07-10)

## Corpus Check
- Large corpus: 1043 files � ~1,518,703 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 244 nodes · 366 edges · 58 communities (51 shown, 7 thin omitted)
- Extraction: 76% EXTRACTED · 24% INFERRED · 0% AMBIGUOUS · INFERRED: 89 edges (avg confidence: 0.81)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Routing & Attendance Controllers|Routing & Attendance Controllers]]
- [[_COMMUNITY_Deployment & Config Docs|Deployment & Config Docs]]
- [[_COMMUNITY_Participant Registration|Participant Registration]]
- [[_COMMUNITY_Attendance & Advanced Export|Attendance & Advanced Export]]
- [[_COMMUNITY_Bootstrap, CSRF & Signature|Bootstrap, CSRF & Signature]]
- [[_COMMUNITY_CSV Import Pipeline|CSV Import Pipeline]]
- [[_COMMUNITY_Registrants & QR Generation|Registrants & QR Generation]]
- [[_COMMUNITY_Composer Manifest|Composer Manifest]]
- [[_COMMUNITY_Report Templates|Report Templates]]
- [[_COMMUNITY_Front Controller & Router|Front Controller & Router]]
- [[_COMMUNITY_Event Management|Event Management]]
- [[_COMMUNITY_CSV Export|CSV Export]]
- [[_COMMUNITY_Admin Settings|Admin Settings]]
- [[_COMMUNITY_Validation|Validation]]

## God Nodes (most connected - your core abstractions)
1. `Database` - 44 edges
2. `csrf_check()` - 19 edges
3. `AdminImportController` - 16 edges
4. `DEPLOYMENT.md (Hostinger Deployment Guide)` - 13 edges
5. `file_get_contents()` - 9 edges
6. `ReportController` - 9 edges
7. `QrService` - 9 edges
8. `README.md (Project Overview)` - 9 edges
9. `SignatureService` - 8 edges
10. `CHECKLIST.md (Pre-Deployment Checklist)` - 8 edges

## Surprising Connections (you probably didn't know these)
- `CHECKLIST.md (Pre-Deployment Checklist)` --semantically_similar_to--> `QUICK_START.txt (Quick Deployment Guide)`  [INFERRED] [semantically similar]
  CHECKLIST.md → QUICK_START.txt
- `QUICK_START.txt (Quick Deployment Guide)` --semantically_similar_to--> `README.md (Project Overview)`  [INFERRED] [semantically similar]
  QUICK_START.txt → README.md
- `CHECKLIST.md (Pre-Deployment Checklist)` --references--> `digitalbayanihan.site (production domain)`  [EXTRACTED]
  CHECKLIST.md → DEPLOYMENT.md
- `.htaccess (Apache routing + security)` --shares_data_with--> `.env / env.example (environment config)`  [INFERRED]
  README.md → DEPLOYMENT.md
- `.htaccess (Apache routing + security)` --implements--> `Apache mod_rewrite (required for routing)`  [INFERRED]
  README.md → DEPLOYMENT.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Hostinger deployment workflow** — deployment_md, quick_start_txt, checklist_md, migration_summary_md, concept_env_file, concept_run_migrations_script, concept_seed_admin_script, concept_hostinger_hpanel [INFERRED 0.95]
- **Apache request routing pipeline** — concept_htaccess, concept_mod_rewrite, concept_index_php, concept_admin_login_route, concept_admin_settings_route [INFERRED 0.85]
- **App bootstrap + migration chain** — concept_config_bootstrap, concept_database_service, concept_run_migrations_script, concept_admins_table [INFERRED 0.85]

## Communities (58 total, 7 thin omitted)

### Community 0 - "Routing & Attendance Controllers"
Cohesion: 0.08
Nodes (8): AdminAttendanceGalleryController, AdminExportPageController, AdminLogsController, AttendanceController, AuthController, ParticipantController, SampleCsvController, ScanController

### Community 1 - "Deployment & Config Docs"
Cohesion: 0.14
Nodes (25): CHECKLIST.md (Pre-Deployment Checklist), ?r=admin_login (admin login route), ?r=admin_settings (admin settings / SMTP route), admins (database table), config/bootstrap.php, App\Services\Database (Database::migrate / Database::pdo), digitalbayanihan.site (production domain), .env / env.example (environment config) (+17 more)

### Community 2 - "Participant Registration"
Cohesion: 0.10
Nodes (5): RegisterController, Mailer, ParticipantValidator, RateLimiter, Uuid

### Community 3 - "Attendance & Advanced Export"
Cohesion: 0.17
Nodes (4): AdminAttendanceController, AdvancedExportController, Database, PDO

### Community 4 - "Bootstrap, CSRF & Signature"
Cohesion: 0.19
Nodes (5): csrf_check(), file_get_contents(), AdminSignatureController, Logger, SignatureService

### Community 6 - "Registrants & QR Generation"
Cohesion: 0.22
Nodes (3): AdminRegistrantsController, PDO, QrService

### Community 7 - "Composer Manifest"
Cohesion: 0.22
Nodes (8): autoload, psr-4, description, name, App\\, require, tecnickcom/tcpdf, type

## Knowledge Gaps
- **10 isolated node(s):** `name`, `description`, `type`, `tecnickcom/tcpdf`, `App\\` (+5 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **7 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Attendance & Advanced Export` to `Routing & Attendance Controllers`, `Participant Registration`, `Bootstrap, CSRF & Signature`, `CSV Import Pipeline`, `Registrants & QR Generation`, `Report Templates`, `Event Management`, `CSV Export`?**
  _High betweenness centrality (0.194) - this node is a cross-community bridge._
- **Why does `csrf_check()` connect `Bootstrap, CSRF & Signature` to `Routing & Attendance Controllers`, `Participant Registration`, `CSV Import Pipeline`, `Registrants & QR Generation`, `Report Templates`, `Event Management`, `Admin Settings`?**
  _High betweenness centrality (0.064) - this node is a cross-community bridge._
- **Are the 34 inferred relationships involving `Database` (e.g. with `.kpiJson()` and `.list()`) actually correct?**
  _`Database` has 34 INFERRED edges - model-reasoned connections that need verification._
- **Are the 18 inferred relationships involving `csrf_check()` (e.g. with `.manualAttendance()` and `.create()`) actually correct?**
  _`csrf_check()` has 18 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `file_get_contents()` (e.g. with `.manualAttendance()` and `.addNew()`) actually correct?**
  _`file_get_contents()` has 8 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _11 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Routing & Attendance Controllers` be split into smaller, more focused modules?**
  _Cohesion score 0.0784313725490196 - nodes in this community are weakly interconnected._