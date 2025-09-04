import pandas as pd
import json

# === Paths ke file kamu ===
csv_path = "role_management.csv"
divisions_path = "divisions.json"
positions_path = "positions.json"
menus_path = "list_menu.json"
output_path = "role_management_final.csv"

# === Load data ===
df = pd.read_csv(csv_path)

with open(divisions_path) as f:
    divisions_json = json.load(f)
with open(positions_path) as f:
    positions_json = json.load(f)
with open(menus_path) as f:
    menus_json = json.load(f)

# Ambil nama resmi dari DB
divisions = {int(d["id_division"]): d["name"] for d in divisions_json[2]["data"]}
positions = {int(p["id_position"]): p["name"] for p in positions_json[2]["data"]}
menu_ids = [int(m["id_listmenu"]) for m in menus_json[2]["data"]]

valid_divisions = set(divisions.values())
valid_positions = set(positions.values())

# Filter hanya yang valid (buang yang gak cocok)
df = df[df["division"].isin(valid_divisions) & df["position"].isin(valid_positions)]

# Pastikan semua kolom permission ada
for col in ["can_create","can_view","can_edit","can_delete",
            "can_approve","can_reject","can_import","can_export"]:
    if col not in df.columns:
        df[col] = 0

# === Tambahin Super Admin full akses ===
superadmin_rows = []
for menu_id in menu_ids:
    superadmin_rows.append({
        "division": "Super Admin",
        "position": "Super Admin",
        "menu_id": menu_id,
        "can_create": 1,
        "can_view": 1,
        "can_edit": 1,
        "can_delete": 1,
        "can_approve": 1,
        "can_reject": 1,
        "can_import": 1,
        "can_export": 1,
    })

df = pd.concat([df, pd.DataFrame(superadmin_rows)], ignore_index=True)

# Ambil kolom final
final_cols = ["division","position","menu_id",
              "can_create","can_view","can_edit","can_delete",
              "can_approve","can_reject","can_import","can_export"]

final_df = df[final_cols].copy()
final_df.to_csv(output_path, index=False)

print(f"✅ File berhasil dibuat dengan Super Admin: {output_path}")


# import pandas as pd
# import json

# # === Paths ke file kamu ===
# csv_path = "role_management.csv"
# divisions_path = "divisions.json"
# positions_path = "positions.json"
# output_path = "role_management_final.csv"

# # === Load data ===
# df = pd.read_csv(csv_path)

# with open(divisions_path) as f:
#     divisions_json = json.load(f)
# with open(positions_path) as f:
#     positions_json = json.load(f)

# # Ambil nama resmi dari DB
# divisions = {int(d["id_division"]): d["name"] for d in divisions_json[2]["data"]}
# positions = {int(p["id_position"]): p["name"] for p in positions_json[2]["data"]}

# valid_divisions = set(divisions.values())
# valid_positions = set(positions.values())

# # Filter hanya yang valid (buang yang gak cocok)
# df = df[df["division"].isin(valid_divisions) & df["position"].isin(valid_positions)]

# # Pastikan semua kolom permission ada
# for col in ["can_create","can_view","can_edit","can_delete",
#             "can_approve","can_reject","can_import","can_export"]:
#     if col not in df.columns:
#         df[col] = 0

# # Ambil kolom final
# final_cols = ["division","position","menu_id",
#               "can_create","can_view","can_edit","can_delete",
#               "can_approve","can_reject","can_import","can_export"]

# final_df = df[final_cols].copy()
# final_df.to_csv(output_path, index=False)

# print(f"✅ File berhasil dibuat: {output_path}")
