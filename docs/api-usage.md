# API Usage Guide (Frontend)

Full API reference: [`openapi.yaml`](../openapi.yaml) — open in [Swagger Editor](https://editor.swagger.io/) for interactive documentation. The spec omits a `servers` entry; set the base URL in Swagger Editor's **Servers** field to match your deployment (e.g. `https://example.com/my-app/api`).

## Base URL

The URL prefix depends on where you deploy the `public/api/` directory. Configure it once for your environment:

```js
const API_BASE = "/api";  // adjust to match your PUBLIC_DIR deployment path
```

For example, if `PUBLIC_DIR` is `/home/user/public_html/files/api/`, the base URL is `/files/api`.

## Response Format

Every endpoint returns JSON with a `status` field:

```js
// Success
{ "status": "success", ...additionalFields }

// Error
{ "status": "error", "message": "Human-readable description" }
```

HTTP status codes: `200` success, `400` invalid input, `405` wrong method, `500` server error.

## Common Error Handling

```js
async function apiFetch(url, options = {}) {
  const res = await fetch(url, options);
  const data = await res.json();
  if (data.status === "error") {
    throw new Error(data.message);
  }
  return data;
}
```

---

## Endpoints

### List directory contents

```js
// GET /list/?path=<directory>
// path="" lists the root. Use "trash/" prefix to list the trash directory.

const data = await apiFetch(
  `${API_BASE}/list/?path=${encodeURIComponent(path)}`
);
// data.list: Array<{ type: "file" | "directory", name: string }>
// Sorted: directories first, then files (natural order)

// List trash
const trash = await apiFetch(`${API_BASE}/list/?path=trash/`);
```

---

### Upload a single file

**Allowed types**: JPEG, PNG, PDF — **Max size**: 100 MB

```js
// POST /upload/
// Use FormData — do NOT set Content-Type manually.
// The browser sets it automatically with the required multipart boundary.

const form = new FormData();
form.append("path", "documents");  // target directory (empty string for root)
form.append("file", fileInput.files[0]);

await apiFetch(`${API_BASE}/upload/`, {
  method: "POST",
  body: form,
  // !! Do not set Content-Type here — let the browser handle it
});
```

---

### Batch image upload

**Allowed types**: JPEG, PNG only — **Limits**: 10 files max, 10 MB per file, 30 MB total

```js
// POST /upload-images/
// Field name must be "images[]" (with brackets) for multi-file upload.

const form = new FormData();
form.append("path", "photos");  // target directory

for (const file of fileInput.files) {
  form.append("images[]", file);  // note the [] suffix
}

const data = await apiFetch(`${API_BASE}/upload-images/`, {
  method: "POST",
  body: form,
  // !! Do not set Content-Type here — let the browser handle it
});
// data.files: string[] — actual filenames saved on disk
// (may differ from original names if a collision was resolved)
```

---

### Rename a file or directory

```js
// POST /rename/
// Conflicts are NOT auto-resolved — returns 400 if newName already exists.

const data = await apiFetch(`${API_BASE}/rename/`, {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: new URLSearchParams({
    path: "documents",  // directory containing the item (empty string for root)
    name: "old-name.txt",
    newName: "new-name.txt",
  }),
});
// data.path: string, data.filename: string
```

---

### Delete a file (move to trash)

This is a **soft delete** — files are moved to the trash directory, not permanently removed.

```js
// POST /delete/

const data = await apiFetch(`${API_BASE}/delete/`, {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: new URLSearchParams({
    path: "documents",  // directory containing the file (empty string for root)
    name: "old-report.pdf",
  }),
});
// data.path: string, data.filename: string (name in trash, may differ if collision)
```

---

### Move a file or directory

```js
// POST /move/
// Filename conflicts are auto-resolved with sequential naming (e.g. "file (1).txt").

const data = await apiFetch(`${API_BASE}/move/`, {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: new URLSearchParams({
    path: "inbox",              // source directory (empty string for root)
    name: "report.pdf",
    destinationPath: "archive/2024",  // must be non-empty
  }),
});
// data.path: string (destination), data.filename: string
```

---

## Key Notes

### Do not manually set `Content-Type` for file uploads

When using `FormData`, the browser automatically sets the `Content-Type` to
`multipart/form-data` and includes the required `boundary` parameter. If you
set `Content-Type` manually (e.g. `"multipart/form-data"`), the boundary will
be missing and the server will fail to parse the request body.

```js
// Correct
fetch(url, { method: "POST", body: formData });

// Broken — boundary is missing
fetch(url, {
  method: "POST",
  headers: { "Content-Type": "multipart/form-data" },
  body: formData,
});
```

### Field name for batch upload is `images[]`

The PHP server reads `$_FILES['images']` as an array. To trigger this behaviour
in a `FormData` POST, each file must be appended under the key `"images[]"`:

```js
form.append("images[]", file1);
form.append("images[]", file2);
```

### Upload limits

| Endpoint | Per-file limit | Total limit | Max files |
|----------|---------------|-------------|-----------|
| `/upload/` | 100 MB | — | 1 |
| `/upload-images/` | 10 MB | 30 MB | 10 |

### Collision handling differences

| Endpoint | On filename conflict |
|----------|---------------------|
| `/upload/` | Auto-renames with sequential suffix (`file (1).jpg`) |
| `/upload-images/` | Auto-renames with sequential suffix |
| `/move/` | Auto-renames with sequential suffix |
| `/delete/` | Auto-renames with sequential suffix (in trash) |
| `/rename/` | Returns **400 error** — you must choose a different name |
