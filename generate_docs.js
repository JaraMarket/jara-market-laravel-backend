const fs = require('fs');
const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, HeadingLevel, WidthType, ShadingType, BorderStyle } = require('docx');

const endpoints = [
  // Auth
  { method: "POST", path: "/api/auth/register", desc: "Register a new user (customer or vendor)" },
  { method: "POST", path: "/api/auth/login", desc: "Authenticate user and receive token" },
  { method: "POST", path: "/api/auth/logout", desc: "Invalidate current user token" },
  { method: "GET", path: "/api/auth/me", desc: "Retrieve currently authenticated user profile" },
  { method: "POST", path: "/api/auth/forgot-password", desc: "Send password reset link to email" },
  { method: "POST", path: "/api/auth/reset-password", desc: "Reset password using received token" },
  { method: "PUT", path: "/api/auth/update-profile", desc: "Update user profile details (name, etc.)" },
  { method: "POST", path: "/api/auth/upload-avatar", desc: "Upload user avatar image to S3" },

  // Customer
  { method: "GET", path: "/api/vendors", desc: "List all approved vendors (supports ?search, ?category)" },
  { method: "GET", path: "/api/vendors/{id}", desc: "Get specific vendor profile and catalog" },
  { method: "GET", path: "/api/categories", desc: "List all product categories" },
  { method: "GET", path: "/api/products", desc: "List all products (supports ?vendor_id)" },
  { method: "GET", path: "/api/products/{id}", desc: "Get specific product details" },
  { method: "POST", path: "/api/orders", desc: "Create a new order for a customer" },
  { method: "GET", path: "/api/orders", desc: "List all orders belonging to the customer" },
  { method: "GET", path: "/api/orders/{id}", desc: "Get specific order details" },
  { method: "PUT", path: "/api/orders/{id}/cancel", desc: "Cancel a pending order" },
  { method: "POST", path: "/api/payments/initiate", desc: "Initialize Paystack transaction (returns auth URL)" },
  { method: "POST", path: "/api/payments/verify", desc: "Verify Paystack transaction and mark order as paid" },
  { method: "GET", path: "/api/payments/history", desc: "Retrieve customer payment history" },
  { method: "POST", path: "/api/vendors/{id}/reviews", desc: "Submit a review/rating for a vendor" },
  { method: "GET", path: "/api/vendors/{id}/reviews", desc: "Retrieve reviews and average rating for a vendor" },
  { method: "POST", path: "/api/notifications/token", desc: "Save Firebase Cloud Messaging (FCM) device token" },
  { method: "GET", path: "/api/notifications", desc: "Retrieve list of in-app notifications" },
  { method: "PUT", path: "/api/notifications/{id}/read", desc: "Mark a specific notification as read" },
  { method: "GET", path: "/api/customers/{id}/reviews", desc: "Retrieve reviews written for a customer" },

  // Vendor
  { method: "GET", path: "/api/vendor/profile", desc: "Get vendor's own business profile" },
  { method: "PUT", path: "/api/vendor/profile", desc: "Update vendor business profile details" },
  { method: "POST", path: "/api/vendor/upload-logo", desc: "Upload vendor business logo to S3" },
  { method: "POST", path: "/api/vendor/upload-banner", desc: "Upload vendor store banner to S3" },
  { method: "GET", path: "/api/vendor/products", desc: "List all products owned by the vendor" },
  { method: "POST", path: "/api/vendor/products", desc: "Create a new product" },
  { method: "GET", path: "/api/vendor/products/{id}", desc: "Get specific vendor product details" },
  { method: "PUT", path: "/api/vendor/products/{id}", desc: "Update a vendor product" },
  { method: "DELETE", path: "/api/vendor/products/{id}", desc: "Delete a vendor product" },
  { method: "POST", path: "/api/vendor/products/{id}/images", desc: "Upload product image to S3" },
  { method: "GET", path: "/api/vendor/orders", desc: "List all orders received by the vendor" },
  { method: "GET", path: "/api/vendor/orders/{id}", desc: "Get specific received order details" },
  { method: "PUT", path: "/api/vendor/orders/{id}/status", desc: "Update order status (accept, ship, deliver)" },
  { method: "GET", path: "/api/vendor/earnings", desc: "Retrieve vendor total earnings and balance" },
  { method: "GET", path: "/api/vendor/payouts", desc: "List requested and completed payouts" },
  { method: "POST", path: "/api/vendor/payouts/request", desc: "Request a payout to a bank account" },
  { method: "POST", path: "/api/customers/{id}/reviews", desc: "Submit a review/rating for a customer" },

  // Admin
  { method: "GET", path: "/api/admin/users", desc: "List all users in the system" },
  { method: "PUT", path: "/api/admin/users/{id}/suspend", desc: "Suspend or un-suspend a user account" },
  { method: "GET", path: "/api/admin/vendors", desc: "List all vendors (including pending ones)" },
  { method: "PUT", path: "/api/admin/vendors/{id}/approve", desc: "Approve a pending vendor application" },
  { method: "PUT", path: "/api/admin/vendors/{id}/reject", desc: "Reject a vendor application" },
  { method: "GET", path: "/api/admin/orders", desc: "List all orders system-wide" },
  { method: "GET", path: "/api/admin/payments", desc: "List all payments system-wide" },
  { method: "GET", path: "/api/admin/categories", desc: "List all categories" },
  { method: "POST", path: "/api/admin/categories", desc: "Create a new product category" },
  { method: "PUT", path: "/api/admin/categories/{id}", desc: "Update a product category" },
  { method: "DELETE", path: "/api/admin/categories/{id}", desc: "Delete a product category" },
  { method: "POST", path: "/api/admin/notifications/send", desc: "Broadcast a push notification to users/vendors" },
  { method: "GET", path: "/api/admin/dashboard/stats", desc: "Retrieve high-level statistics for admin dashboard" },
];

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };

const tableRows = [
  new TableRow({
    children: [
      new TableCell({ width: { size: 1500, type: WidthType.DXA }, shading: { fill: "D5E8F0", type: ShadingType.CLEAR }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph({ children: [new TextRun({ text: "Method", bold: true })] })] }),
      new TableCell({ width: { size: 3500, type: WidthType.DXA }, shading: { fill: "D5E8F0", type: ShadingType.CLEAR }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph({ children: [new TextRun({ text: "Endpoint Path", bold: true })] })] }),
      new TableCell({ width: { size: 4360, type: WidthType.DXA }, shading: { fill: "D5E8F0", type: ShadingType.CLEAR }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph({ children: [new TextRun({ text: "Description", bold: true })] })] }),
    ]
  })
];

endpoints.forEach(ep => {
  tableRows.push(
    new TableRow({
      children: [
        new TableCell({ width: { size: 1500, type: WidthType.DXA }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph(ep.method)] }),
        new TableCell({ width: { size: 3500, type: WidthType.DXA }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph(ep.path)] }),
        new TableCell({ width: { size: 4360, type: WidthType.DXA }, margins: { top: 80, bottom: 80, left: 120, right: 120 }, borders, children: [new Paragraph(ep.desc)] }),
      ]
    })
  );
});

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: "000000" },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 0 } },
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    children: [
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("JaraMarket API Endpoints Reference")] }),
      new Paragraph({ children: [new TextRun("Below is the complete, documented list of all API endpoints for the JaraMarket platform, including the recently added Customer and Vendor review features.")] }),
      new Paragraph({ text: "" }),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [1500, 3500, 4360],
        rows: tableRows
      })
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("JaraMarket_API_Endpoints.docx", buffer);
  console.log("JaraMarket_API_Endpoints.docx created successfully!");
}).catch(console.error);
