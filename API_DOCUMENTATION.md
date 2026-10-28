# Rich Mount ERP — Mobile REST API Specification (v1)

This document provides complete, production-ready REST API specifications for the **Rich Mount ERP** Flutter mobile application and external service integrations.

---

## 1. Overview & Conventions

* **Base URL**: `https://erp.richmount.ae/api/v1` (Production) / `http://localhost:8000/api/v1` (Local Dev)
* **Authentication**: Bearer Token via Laravel Sanctum
* **Default Content-Type**: `application/json` (except multipart file uploads)
* **Standard Headers**:
  ```http
  Accept: application/json
  Authorization: Bearer <SANCTUM_TOKEN>
  ```

### Standard Response Envelope
All successful responses return JSON matching this schema:
```json
{
  "success": true,
  "message": "Optional human-readable feedback",
  "data": { ... }
}
```

### Standard Validation Error Response (HTTP 422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "subtotal": ["The subtotal must be at least 0.01."]
  }
}
```

### Unauthorized / Forbidden (HTTP 401 / 403)
```json
{
  "message": "Unauthenticated."
}
```
```json
{
  "message": "This action is unauthorized."
}
```

---

## 2. Authentication Endpoints

### 2.1 Login & Obtain Bearer Token
* **Endpoint**: `POST /auth/login`
* **Access**: Public
* **Request Body**:
  ```json
  {
    "email": "engineer.dxb@richmount.ae",
    "password": "password",
    "device_name": "iPhone 15 Pro Max"
  }
  ```
* **Response (HTTP 200)**:
  ```json
  {
    "success": true,
    "token": "1|qXyZ84...sanctum_token_string",
    "user": {
      "id": 3,
      "name": "Omar Al-Sayed",
      "email": "engineer.dxb@richmount.ae",
      "role": "engineer",
      "engineer_code": "ENG-DXB-01",
      "branch": {
        "id": 1,
        "name": "Dubai Headquarters",
        "code": "DXB"
      },
      "cash_balance": "5000.00",
      "permissions": [
        "leads.view",
        "quotes.view",
        "projects.view",
        "expenses.create",
        "transfers.create"
      ]
    }
  }
  ```

### 2.2 Current User Profile (`Me`)
* **Endpoint**: `GET /user/me`
* **Access**: Authenticated
* **Response (HTTP 200)**:
  ```json
  {
    "success": true,
    "user": {
      "id": 3,
      "name": "Omar Al-Sayed",
      "email": "engineer.dxb@richmount.ae",
      "role": "engineer",
      "branch_id": 1,
      "cash_balance": "4370.00"
    }
  }
  ```

### 2.3 Logout & Revoke Current Token
* **Endpoint**: `POST /auth/logout`
* **Access**: Authenticated
* **Response (HTTP 200)**:
  ```json
  {
    "success": true,
    "message": "Logged out successfully. Token revoked."
  }
  ```

---

## 3. Dashboard Endpoint

### 3.1 Fetch Live Dashboard Aggregate Metrics
* **Endpoint**: `GET /dashboard`
* **Access**: Authenticated (Role-Scoped)
* **Response (HTTP 200)**:
  ```json
  {
    "success": true,
    "data": {
      "kpis": [
        {
          "key": "my_leads",
          "label": "My Leads",
          "value": "8",
          "icon": "users",
          "note": "Assigned site leads",
          "href": "http://localhost:8000/leads"
        },
        {
          "key": "cash_in_hand",
          "label": "Cash in Hand",
          "value": "AED 4,370.00",
          "icon": "coins",
          "note": "Current petty cash float",
          "href": "http://localhost:8000/expenses"
        }
      ],
      "pipeline": {
        "new": { "name": "New Leads", "count": 3, "items": [ ... ] },
        "draft": { "name": "Draft Quotation", "count": 2, "items": [ ... ] },
        "sent": { "name": "Sent to Client", "count": 4, "items": [ ... ] },
        "awarded": { "name": "Awarded", "count": 5, "items": [ ... ] }
      },
      "recent_leads": {
        "data": [
          {
            "id": 1,
            "lead_number": "RM-LD-2026-0001",
            "customer_name": "Emaar Properties PJSC",
            "company_name": "Emaar Development",
            "status": "proposal"
          }
        ]
      }
    }
  }
  ```

---

## 4. Leads Management API

### 4.1 List Leads
* **Endpoint**: `GET /leads`
* **Query Parameters**:
  * `status`: `new` | `contacted` | `proposal` | `negotiation` | `won` | `lost`
  * `search`: Customer name, company name, RFQ, or subject
  * `branch_id`: Filter by branch ID
  * `per_page`: Default `15`
* **Response (HTTP 200)**:
  ```json
  {
    "success": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "lead_number": "RM-LD-2026-0001",
          "customer_name": "Emaar Properties PJSC",
          "company_name": "Emaar Development",
          "rfq_number": "RFQ-EMR-2026-99",
          "subject": "Dubai Mall Fashion Avenue Marble Restoration",
          "department": "Civil Contracting",
          "status": "proposal",
          "estimated_value": "450000.00",
          "assigned_engineer": {
            "id": 3,
            "name": "Omar Al-Sayed"
          },
          "branch": {
            "id": 1,
            "name": "Dubai Headquarters"
          }
        }
      ]
    }
  }
  ```

### 4.2 Create New Lead
* **Endpoint**: `POST /leads`
* **Request Body**:
  ```json
  {
    "customer_name": "Nakheel PJSC",
    "company_name": "Nakheel Marine Works",
    "rfq_number": "RFQ-NKH-2026-10",
    "subject": "Palm Jumeirah Boardwalk Structural Repairs",
    "branch_id": 1,
    "department": "civil",
    "source": "referral",
    "estimated_value": 320000.00,
    "contact_person": "Eng. Rashid Al Nuaimi",
    "phone": "+971 4 390 3333",
    "email": "rashid.nuaimi@nakheel.ae",
    "notes": "Fast track project commencement required within 3 weeks."
  }
  ```
* **Response (HTTP 201)**:
  ```json
  {
    "success": true,
    "message": "Lead created successfully.",
    "lead": {
      "id": 14,
      "lead_number": "RM-LD-2026-0014",
      "customer_name": "Nakheel PJSC",
      "status": "new"
    }
  }
  ```

### 4.3 Get Single Lead Details
* **Endpoint**: `GET /leads/{id}`
* **Response (HTTP 200)**: Full lead object with quotations, activities, and attachments.

### 4.4 Update Lead
* **Endpoint**: `PUT /leads/{id}`
* **Request Body**: Same fields as creation (partial updates supported).

### 4.5 Assign Lead to Engineer
* **Endpoint**: `POST /leads/{id}/assign`
* **Request Body**:
  ```json
  {
    "engineer_id": 3
  }
  ```

---

## 5. Quotations API

### 5.1 List Quotations
* **Endpoint**: `GET /quotations`
* **Query Parameters**: `tab` (`draft`, `pending`, `sent`, `awarded`), `search`, `per_page`
* **Response (HTTP 200)**: Paginated quotations with totals and client info.

### 5.2 Create Quotation
* **Endpoint**: `POST /quotations`
* **Request Body**:
  ```json
  {
    "lead_id": 1,
    "quote_date": "2026-10-25",
    "validity_days": 30,
    "payment_terms": "30% Advance, 60% Progress, 10% Retention",
    "items": [
      {
        "description": "Surface hydro-jetting and concrete preparation",
        "quantity": 1200,
        "unit": "sqm",
        "unit_price": 45.00
      },
      {
        "description": "Supply & application of elastomeric waterproofing membrane",
        "quantity": 1200,
        "unit": "sqm",
        "unit_price": 120.00
      }
    ]
  }
  ```

### 5.3 Quotation Workflow Actions
* `POST /quotations/{id}/autosave`: Partial state update during draft editing.
* `POST /quotations/{id}/clone`: Clones quotation into new revision (`-R1`).
* `POST /quotations/{id}/submit`: Engineer submits draft for Estimation/Admin review.
* `POST /quotations/{id}/approve`: Estimation/Admin approves quotation.
* `POST /quotations/{id}/reject`: Rejects quotation with reason.
* `POST /quotations/{id}/send`: Marks quotation as Sent to customer.
* `POST /quotations/{id}/award`: Marks quotation as Won/Awarded & triggers Project PO Pending.

---

## 6. Projects API

### 6.1 List Projects
* **Endpoint**: `GET /projects`
* **Query Parameters**: `status` (`popending`, `open`, `inprogress`, `completed`, `invoicepending`, `closed`), `search`

### 6.2 Get Project Details
* **Endpoint**: `GET /projects/{id}`
* **Response (HTTP 200)**: Complete project lifecycle, progress %, purchase orders, WCC, invoices, and expenses.

### 6.3 Record Client PO (Project Activation)
* **Endpoint**: `POST /projects/{id}/po`
* **Content-Type**: `multipart/form-data`
* **Fields**:
  * `client_po_number`: string (required)
  * `client_po_date`: YYYY-MM-DD
  * `client_po_amount`: numeric
  * `po_file`: PDF/Image file upload

### 6.4 Issue Work Completion Certificate (WCC)
* **Endpoint**: `POST /projects/{id}/complete-work`
* **Request Body**:
  ```json
  {
    "completion_date": "2026-10-27",
    "notes": "All site civil and MEP snag items closed and approved by consultant.",
    "defect_liability_months": 12
  }
  ```

---

## 7. Invoices & Payments API

### 7.1 List Invoices
* **Endpoint**: `GET /invoices`
* **Query Parameters**: `status`, `project_id`, `search`

### 7.2 Create Project Invoice
* **Endpoint**: `POST /projects/{project_id}/invoices`
* **Request Body**:
  ```json
  {
    "invoice_date": "2026-10-27",
    "due_date": "2026-11-26",
    "is_final": false,
    "client_trn": "100987654300003",
    "items": [
      {
        "description": "Milestone Claim: 40% Completion of Waterproofing works",
        "quantity": 1,
        "unit": "lump sum",
        "unit_price": 75000.00
      }
    ]
  }
  ```

### 7.3 Record Customer Payment
* **Endpoint**: `POST /invoices/{id}/payment`
* **Request Body**:
  ```json
  {
    "amount": 78750.00,
    "payment_date": "2026-10-27",
    "payment_method": "bank_transfer",
    "reference_number": "TXN-EMR-883910",
    "notes": "Full settlement received in Emirates NBD corporate account."
  }
  ```

---

## 8. Expenses & Petty Cash API

### 8.1 List Expenses
* **Endpoint**: `GET /expenses`
* **Query Parameters**: `status` (`pending`, `approved`, `rejected`), `type` (`cash`, `card`), `per_page`

### 8.2 Create Expense (With Mobile Receipt Upload)
* **Endpoint**: `POST /expenses`
* **Content-Type**: `multipart/form-data`
* **Fields**:
  * `project_id`: integer (nullable, if general overhead)
  * `branch_id`: integer
  * `category`: `materials` | `transport` | `labor` | `tools` | `fuel` | `permits` | `miscellaneous`
  * `vendor_name`: string
  * `vendor_trn`: string (optional, 15 digits)
  * `date`: YYYY-MM-DD
  * `type`: `cash` | `card`
  * `subtotal`: numeric
  * `vat_rate`: 0.05
  * `description`: string
  * `receipt`: Image file (JPEG/PNG/HEIC/PDF up to 10MB)

---

## 9. Internal Float Transfers API

### 9.1 List Transfers
* **Endpoint**: `GET /transfers`
* **Response (HTTP 200)**: List of petty cash float disbursements.

### 9.2 Submit Float Request
* **Endpoint**: `POST /transfers`
* **Request Body**:
  ```json
  {
    "to_user_id": 3,
    "amount": 2500.00,
    "payment_method": "bank_transfer",
    "notes": "Emergency site petty cash float replenishment for concrete testing fees."
  }
  ```

---

## 10. Flutter Mobile Client Integration Example (Dart)

```dart
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  static const String baseUrl = 'https://erp.richmount.ae/api/v1';
  final Dio _dio = Dio(BaseOptions(baseUrl: baseUrl, headers: {'Accept': 'application/json'}));
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  ApiService() {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'sanctum_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
    ));
  }

  // Login
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _dio.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': Platform.operatingSystem,
    });
    final token = response.data['token'];
    await _storage.write(key: 'sanctum_token', value: token);
    return response.data;
  }

  // Upload Site Expense with Camera Receipt
  Future<Response> uploadExpense({
    required int? projectId,
    required String vendorName,
    required double subtotal,
    required String type,
    required File receiptImage,
  }) async {
    final formData = FormData.fromMap({
      if (projectId != null) 'project_id': projectId,
      'vendor_name': vendorName,
      'subtotal': subtotal,
      'type': type,
      'date': DateTime.now().toIso8601String().substring(0, 10),
      'receipt': await MultipartFile.fromFile(receiptImage.path, filename: 'receipt.jpg'),
    });

    return await _dio.post('/expenses', data: formData);
  }
}
```
