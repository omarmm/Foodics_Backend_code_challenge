# Foodics Backend Code Challenge Project

## Project Overview

This Laravel application manages **Products**, **Ingredients**, and **Orders**. 

- **Product**: Represents items like a Burger.
- **Ingredient**: Tracks components like Beef, Cheese, and Onion.
- **Order**: Records customer purchases and updates ingredient stock.

**Key Features:**

- **Stock Management**: Tracks ingredient stock and updates it when orders are placed.
- **Email Alerts**: Sends a notification when any ingredient’s stock falls below 50%, ensuring merchants are alerted to restock. 

## Requirements

1. **Controller Action**: Handles order requests, saves orders, and updates ingredient stock.
2. **Email Notification**: Sends a single alert per ingredient when stock drops below 50%.
3. **Testing**: Includes tests to validate order storage and stock updates.


## Prerequisites

Ensure you have the following installed:

- [PHP](https://www.php.net/manual/en/install.php) (version 8.0 or higher)
- [Composer](https://getcomposer.org/)


## Installation

Follow these steps to set up the project locally:

1. **Clone the Repository**

   ```bash
   git clone https://github.com/omarmm/Foodics_Backend_code_challenge.git
   cd Foodics_Backend_code_challenge

2. **Install Dependencies**
   ```bash
   composer install

3. **Set Up Environment**
   ```bash
   cp .env.example .env

4. **Generate Application Key**
   ```bash
   php artisan key:generate
5. **Run Migrations (SQLite)**
   ```bash
   php artisan migrate

6. **Run Tests**
   ```bash
   php artisan test



