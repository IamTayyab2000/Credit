import csv
import random
from datetime import datetime, timedelta

# Sample data pools
first_names = ["Ali", "Ahmed", "Usman", "Hassan", "Bilal", "Omar", "Zain", "Saad", "Hamza", "Ayaan"]
last_names = ["Khan", "Ahmed", "Malik", "Raza", "Shah", "Butt", "Sheikh", "Chaudhry", "Iqbal", "Farooq"]

areas = [
    "Model Town", "Gulshan-e-Iqbal", "DHA Phase 1", "DHA Phase 2",
    "Johar Town", "Bahria Town", "Clifton Block 5",
    "Satellite Town", "Cantt Area", "Defence View"
]

salesmen = ["Imran", "Faisal", "Naveed", "Asif", "Kashif", "Tariq"]

# Create mapping: each area is assigned to only one salesman
area_to_salesman = {}
for area in areas:
    area_to_salesman[area] = random.choice(salesmen)

# Create CSV file
with open("customers_data.csv", mode="w", newline="", encoding="utf-8") as file:
    writer = csv.writer(file)
    
    # Write header
    writer.writerow([
        "customer_id",
        "customer_name",
        "area_address",
        "salesman_name",
        "bill_amount",
        "remaining_credit",
        "bill_date"
    ])
    
    # Generate 500 records
    for i in range(1, 501):
        customer_id = i
        customer_name = random.choice(first_names) + " " + random.choice(last_names)
        area_address = random.choice(areas)
        salesman_name = area_to_salesman[area_address]  # Get assigned salesman for this area
        
        bill_amount = round(random.uniform(1000, 50000), 2)
        remaining_credit = round(random.uniform(0, bill_amount), 2)
        
        # Generate random bill date from the past 24 months
        days_ago = random.randint(0, 730)
        bill_date = (datetime.now() - timedelta(days=days_ago)).strftime("%Y-%m-%d")
        
        writer.writerow([
            customer_id,
            customer_name,
            area_address,
            salesman_name,
            bill_amount,
            remaining_credit,
            bill_date
        ])

print("CSV file 'customers_data.csv' with 500 records created successfully.")
print("\nArea to Salesman Mapping:")
for area, salesman in area_to_salesman.items():
    print(f"  {area}: {salesman}")