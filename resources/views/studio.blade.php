@extends('laramyadmin::layout')

@section('content')
<div id="laramyadmin-app" class="flex flex-col h-screen overflow-hidden bg-slate-950 text-slate-100" v-cloak>
    <!-- Top Navigation Bar -->
    <header class="h-14 border-b border-slate-800 bg-slate-900/80 backdrop-blur px-4 flex items-center justify-between shrink-0 z-20">
        <div class="flex items-center space-x-4">
            <!-- Brand -->
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                    <i data-lucide="database" class="w-4 h-4 text-slate-950 stroke-[2.5]"></i>
                </div>
                <div>
                    <span class="font-bold text-base tracking-tight bg-gradient-to-r from-teal-300 via-emerald-200 to-cyan-300 bg-clip-text text-transparent">LaraMyAdmin</span>
                    <span class="text-[10px] uppercase font-mono tracking-wider ml-1.5 px-1.5 py-0.5 rounded bg-teal-950/80 border border-teal-800/60 text-teal-400">Studio</span>
                </div>
            </div>

            <!-- Connection Switcher -->
            <div class="relative">
                <div class="flex items-center bg-slate-800/90 border border-slate-700/80 rounded-lg p-1 space-x-1">
                    <div class="flex items-center px-2 py-1 space-x-2 text-xs">
                        <span class="w-2 h-2 rounded-full" :class="activeConnection ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                        <span class="text-slate-400 font-medium">Connection:</span>
                    </div>
                    <select v-model="selectedConnection" @change="switchConnection" class="bg-slate-900 border border-slate-700 rounded-md text-xs px-2.5 py-1 text-slate-200 focus:outline-none focus:border-teal-500 cursor-pointer font-mono">
                        <option v-for="conn in connections" :key="conn.name" :value="conn.name">
                            @{{ conn.name }} (@{{ conn.driver }}) @{{ conn.is_default ? '★ default' : '' }} @{{ conn.is_dynamic ? '⚡ dynamic' : '' }}
                        </option>
                    </select>
                    <button @click="showAddConnectionModal = true" class="p-1.5 hover:bg-slate-700 rounded-md text-slate-300 hover:text-teal-300 transition" title="Add New Connection">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Global Navigation & Action Buttons -->
        <div class="flex items-center space-x-2">
            <!-- Mode Badges -->
            <span v-if="config.readOnly" class="text-xs px-2 py-1 rounded bg-amber-950/80 border border-amber-800/60 text-amber-400 font-medium flex items-center space-x-1">
                <i data-lucide="lock" class="w-3 h-3"></i>
                <span>Read-Only</span>
            </span>

            <!-- Top Nav Tabs -->
            <div class="flex items-center bg-slate-800/60 border border-slate-800 rounded-lg p-0.5">
                <button @click="activeMainTab = 'tables'" :class="activeMainTab === 'tables' ? 'bg-teal-600 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="table-2" class="w-3.5 h-3.5"></i>
                    <span>Tables</span>
                </button>
                <button @click="activeMainTab = 'query'" :class="activeMainTab === 'query' ? 'bg-teal-600 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                    <span>SQL Console</span>
                </button>
                <button @click="activeMainTab = 'info'" :class="activeMainTab === 'info' ? 'bg-teal-600 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="server" class="w-3.5 h-3.5"></i>
                    <span>Database Info</span>
                </button>
            </div>

            <!-- Import / Export Actions -->
            <button @click="showImportModal = true" class="px-2.5 py-1 text-xs rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 transition flex items-center space-x-1.5">
                <i data-lucide="upload" class="w-3.5 h-3.5 text-teal-400"></i>
                <span>Import</span>
            </button>
            <a :href="'/' + config.path + '/export/sql'" target="_blank" class="px-2.5 py-1 text-xs rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 transition flex items-center space-x-1.5">
                <i data-lucide="download" class="w-3.5 h-3.5 text-teal-400"></i>
                <span>Dump SQL</span>
            </a>

            <!-- Refresh Button -->
            <button @click="refreshAll" class="p-1.5 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 transition" title="Refresh">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="isLoading ? 'animate-spin text-teal-400' : ''"></i>
            </button>
        </div>
    </header>

    <!-- Main Workspace (Sidebar + Canvas) -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 border-r border-slate-800 bg-slate-900/50 flex flex-col shrink-0">
            <!-- Sidebar Header & Search -->
            <div class="p-3 border-b border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Database Objects</span>
                    <button @click="showCreateTableModal = true" class="px-2 py-0.5 text-[11px] rounded bg-teal-900/60 hover:bg-teal-800/80 border border-teal-700/60 text-teal-300 font-medium transition flex items-center space-x-1">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>Table</span>
                    </button>
                </div>
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-500"></i>
                    <input v-model="tableSearch" type="text" placeholder="Filter tables..." class="w-full bg-slate-800 border border-slate-700 rounded-md pl-8 pr-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                </div>
            </div>

            <!-- Tables List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-0.5">
                <div v-if="filteredTables.length === 0" class="text-center py-8 text-xs text-slate-500">
                    No tables found.
                </div>
                <div v-for="t in filteredTables" :key="t.name" 
                     @click="selectTable(t.name)"
                     :class="selectedTableName === t.name ? 'bg-teal-950/80 border-teal-700 text-teal-300 shadow-sm' : 'border-transparent text-slate-300 hover:bg-slate-800/60'"
                     class="group flex items-center justify-between px-2.5 py-1.5 rounded-md border text-xs cursor-pointer transition select-none">
                    <div class="flex items-center space-x-2 truncate">
                        <i :data-lucide="t.type === 'view' ? 'eye' : 'table'" class="w-3.5 h-3.5 shrink-0" :class="selectedTableName === t.name ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300'"></i>
                        <span class="truncate font-mono text-[11px]" :title="t.name">@{{ t.name }}</span>
                    </div>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800/80 text-slate-400 group-hover:text-slate-200 shrink-0 ml-1">
                        @{{ formatNumber(t.rows_count) }}
                    </span>
                </div>
            </div>

            <!-- System Info Footer -->
            <div class="p-3 border-t border-slate-800 bg-slate-900/90 text-[11px] text-slate-400 space-y-1 font-mono">
                <div class="flex justify-between">
                    <span class="text-slate-500">Driver:</span>
                    <span class="text-slate-300">@{{ systemInfo.Driver || '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Database:</span>
                    <span class="text-teal-400 truncate max-w-[120px]" :title="systemInfo.Database">@{{ systemInfo.Database || '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">DB Size:</span>
                    <span class="text-slate-300">@{{ systemInfo['Database Size'] || '-' }}</span>
                </div>
            </div>
        </aside>

        <!-- Main Canvas -->
        <main class="flex-1 flex flex-col overflow-hidden bg-slate-950">
            <!-- Alert / Toast Banner -->
            <div v-if="alertMessage" :class="alertType === 'error' ? 'bg-rose-950 border-rose-800 text-rose-200' : 'bg-emerald-950 border-emerald-800 text-emerald-200'" class="px-4 py-2 border-b text-xs flex items-center justify-between">
                <span>@{{ alertMessage }}</span>
                <button @click="alertMessage = ''" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <!-- VIEW 1: TABLE MANAGER (BROWSE / STRUCTURE) -->
            <div v-if="activeMainTab === 'tables'" class="flex-1 flex flex-col overflow-hidden">
                <div v-if="!selectedTableName" class="flex-1 flex flex-col items-center justify-center text-slate-500 p-8 space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600 shadow-inner">
                        <i data-lucide="layers" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-300">No Table Selected</h3>
                    <p class="text-xs text-slate-500 max-w-sm text-center">Select a table from the left sidebar to browse records, modify table schema, or manage indexes.</p>
                </div>

                <div v-else class="flex-1 flex flex-col overflow-hidden">
                    <!-- Table Top Header -->
                    <div class="px-4 py-2.5 border-b border-slate-800 bg-slate-900/40 flex items-center justify-between shrink-0">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="table" class="w-4 h-4 text-teal-400"></i>
                                <span class="font-mono text-sm font-bold text-slate-100">@{{ selectedTableName }}</span>
                            </div>
                            <!-- Sub Tabs (Browse / Structure) -->
                            <div class="flex items-center bg-slate-800/80 border border-slate-700/80 rounded-lg p-0.5 text-xs">
                                <button @click="activeTableTab = 'browse'" :class="activeTableTab === 'browse' ? 'bg-teal-600 text-white font-medium' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1 rounded transition">
                                    Browse Data (@{{ tableRowsTotal }})
                                </button>
                                <button @click="activeTableTab = 'structure'" :class="activeTableTab === 'structure' ? 'bg-teal-600 text-white font-medium' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1 rounded transition">
                                    Structure & Schema
                                </button>
                            </div>
                        </div>

                        <!-- Table Actions -->
                        <div class="flex items-center space-x-2">
                            <button v-if="activeTableTab === 'browse'" @click="openInsertModal" class="px-2.5 py-1 text-xs rounded-md bg-teal-600 hover:bg-teal-500 text-white font-medium transition flex items-center space-x-1 shadow-sm">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Insert Row</span>
                            </button>
                            <button v-if="activeTableTab === 'browse' && selectedRowKeys.length > 0" @click="deleteSelectedRows" class="px-2.5 py-1 text-xs rounded-md bg-rose-600 hover:bg-rose-500 text-white font-medium transition flex items-center space-x-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Delete (@{{ selectedRowKeys.length }})</span>
                            </button>
                            <div class="relative">
                                <button @click="showExportDropdown = !showExportDropdown" class="px-2.5 py-1 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 transition flex items-center space-x-1">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Export</span>
                                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                </button>
                                <div v-if="showExportDropdown" class="absolute right-0 mt-1 w-36 bg-slate-900 border border-slate-700 rounded-lg shadow-xl py-1 z-30 text-xs">
                                    <a :href="'/' + config.path + '/export/' + selectedTableName + '/csv'" class="block px-3 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-teal-300">Export CSV</a>
                                    <a :href="'/' + config.path + '/export/' + selectedTableName + '/json'" class="block px-3 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-teal-300">Export JSON</a>
                                    <a :href="'/' + config.path + '/export/sql?table=' + selectedTableName" class="block px-3 py-1.5 hover:bg-slate-800 text-slate-300 hover:text-teal-300">Export SQL Dump</a>
                                </div>
                            </div>
                            <button @click="truncateCurrentTable" class="p-1.5 rounded-md border border-slate-700 bg-slate-800 hover:bg-amber-950/80 hover:border-amber-700 text-slate-400 hover:text-amber-400 transition" title="Truncate Table">
                                <i data-lucide="scissors" class="w-3.5 h-3.5"></i>
                            </button>
                            <button @click="dropCurrentTable" class="p-1.5 rounded-md border border-slate-700 bg-slate-800 hover:bg-rose-950/80 hover:border-rose-700 text-slate-400 hover:text-rose-400 transition" title="Drop Table">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- SUB-VIEW 1: BROWSE DATA -->
                    <div v-if="activeTableTab === 'browse'" class="flex-1 flex flex-col overflow-hidden">
                        <!-- Filter & Search Bar -->
                        <div class="px-4 py-2 border-b border-slate-800 bg-slate-900/20 flex flex-wrap items-center justify-between gap-2 shrink-0">
                            <!-- Search & Quick Filter -->
                            <div class="flex items-center space-x-2">
                                <div class="relative w-64">
                                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-500"></i>
                                    <input v-model="dataSearch" @keyup.enter="fetchTableRows(1)" type="text" placeholder="Search rows..." class="w-full bg-slate-800/80 border border-slate-700 rounded-md pl-8 pr-2.5 py-1 text-xs text-slate-200 focus:outline-none focus:border-teal-500">
                                </div>
                                <button @click="fetchTableRows(1)" class="px-2.5 py-1 text-xs rounded-md bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300">Filter</button>
                                <button v-if="dataSearch" @click="dataSearch = ''; fetchTableRows(1)" class="text-xs text-slate-500 hover:text-slate-300">Clear</button>
                            </div>

                            <!-- Pagination Controls Top/Bottom -->
                            <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
                                <span>Rows per page:</span>
                                <select v-model="perPage" @change="fetchTableRows(1)" class="bg-slate-800 border border-slate-700 rounded px-2 py-0.5 text-xs text-slate-200">
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                    <option :value="250">250</option>
                                </select>
                                <span class="mx-2">|</span>
                                <span>Page @{{ currentPage }} of @{{ lastPage }}</span>
                                <div class="flex items-center space-x-1">
                                    <button :disabled="currentPage <= 1" @click="fetchTableRows(currentPage - 1)" class="p-1 rounded bg-slate-800 border border-slate-700 disabled:opacity-30 hover:bg-slate-700 text-slate-200">
                                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button :disabled="currentPage >= lastPage" @click="fetchTableRows(currentPage + 1)" class="p-1 rounded bg-slate-800 border border-slate-700 disabled:opacity-30 hover:bg-slate-700 text-slate-200">
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Grid Table -->
                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <table class="w-full text-left text-xs border-collapse font-mono">
                                <thead class="bg-slate-900/90 text-slate-400 sticky top-0 z-10 border-b border-slate-800 shadow-sm backdrop-blur">
                                    <tr>
                                        <th class="p-2.5 w-10 text-center">
                                            <input type="checkbox" @change="toggleSelectAllRows" :checked="isAllSelected" class="rounded bg-slate-800 border-slate-700 text-teal-600 focus:ring-0">
                                        </th>
                                        <th class="p-2.5 w-24 text-center text-slate-500 font-sans uppercase text-[10px] tracking-wider">Actions</th>
                                        <th v-for="col in tableColumns" :key="col.name" @click="sortBy(col.name)" class="p-2.5 text-slate-300 font-semibold cursor-pointer hover:bg-slate-800/80 transition select-none whitespace-nowrap">
                                            <div class="flex items-center space-x-1.5">
                                                <span>@{{ col.name }}</span>
                                                <span v-if="col.primary" class="text-[9px] px-1 rounded bg-amber-950 border border-amber-800 text-amber-400 font-normal">PK</span>
                                                <i v-if="sortCol === col.name" :data-lucide="sortDir === 'asc' ? 'arrow-up' : 'arrow-down'" class="w-3 h-3 text-teal-400"></i>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                    <tr v-if="tableRows.length === 0">
                                        <td :colspan="tableColumns.length + 2" class="text-center py-12 text-slate-500">
                                            No rows found in this table.
                                        </td>
                                    </tr>
                                    <tr v-for="(row, rIndex) in tableRows" :key="rIndex" class="hover:bg-slate-900/70 transition group">
                                        <!-- Checkbox -->
                                        <td class="p-2.5 text-center">
                                            <input type="checkbox" :value="getRowKey(row)" v-model="selectedRowKeys" class="rounded bg-slate-800 border-slate-700 text-teal-600 focus:ring-0">
                                        </td>
                                        <!-- Actions -->
                                        <td class="p-2.5 text-center">
                                            <div class="flex items-center justify-center space-x-1 opacity-60 group-hover:opacity-100 transition">
                                                <button @click="openEditModal(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-teal-300" title="Edit Record">
                                                    <i data-lucide="pencil" class="w-3 h-3"></i>
                                                </button>
                                                <button @click="duplicateRecord(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-cyan-300" title="Clone Record">
                                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                                </button>
                                                <button @click="deleteSingleRow(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-rose-400" title="Delete Record">
                                                    <i data-lucide="trash" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <!-- Cells -->
                                        <td v-for="col in tableColumns" :key="col.name" class="p-2.5 text-slate-300 max-w-xs truncate border-r border-slate-900" :title="formatCellValue(row[col.name])">
                                            <span v-if="row[col.name] === null" class="text-slate-600 italic">NULL</span>
                                            <span v-else-if="typeof row[col.name] === 'boolean'" class="px-1.5 py-0.5 rounded text-[10px]" :class="row[col.name] ? 'bg-emerald-950 text-emerald-400' : 'bg-rose-950 text-rose-400'">
                                                @{{ row[col.name] ? 'TRUE' : 'FALSE' }}
                                            </span>
                                            <span v-else>@{{ formatCellValue(row[col.name]) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SUB-VIEW 2: TABLE STRUCTURE & SCHEMA -->
                    <div v-if="activeTableTab === 'structure'" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                        <!-- Columns Header -->
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-200">Table Columns (@{{ tableColumns.length }})</h4>
                            <button @click="showAddColumnModal = true" class="px-2.5 py-1 text-xs rounded bg-teal-600 hover:bg-teal-500 text-white font-medium flex items-center space-x-1">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Column</span>
                            </button>
                        </div>

                        <!-- Columns Table -->
                        <div class="rounded-lg border border-slate-800 overflow-hidden">
                            <table class="w-full text-left text-xs font-mono">
                                <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="p-2.5">Name</th>
                                        <th class="p-2.5">Type</th>
                                        <th class="p-2.5">Null</th>
                                        <th class="p-2.5">Default</th>
                                        <th class="p-2.5">Key</th>
                                        <th class="p-2.5">Extra</th>
                                        <th class="p-2.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                    <tr v-for="col in tableColumns" :key="col.name" class="hover:bg-slate-900/50">
                                        <td class="p-2.5 font-semibold text-slate-200">@{{ col.name }}</td>
                                        <td class="p-2.5 text-teal-400">@{{ col.full_type || col.type }}</td>
                                        <td class="p-2.5">
                                            <span :class="col.nullable ? 'text-emerald-400' : 'text-slate-500'">@{{ col.nullable ? 'YES' : 'NO' }}</span>
                                        </td>
                                        <td class="p-2.5 text-slate-400">@{{ col.default !== null ? col.default : 'NULL' }}</td>
                                        <td class="p-2.5">
                                            <span v-if="col.primary" class="px-1.5 py-0.5 rounded bg-amber-950 border border-amber-800 text-amber-400 text-[10px]">PRIMARY</span>
                                            <span v-else-if="col.unique" class="px-1.5 py-0.5 rounded bg-cyan-950 border border-cyan-800 text-cyan-400 text-[10px]">UNIQUE</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="p-2.5 text-slate-400">@{{ col.extra || '-' }}</td>
                                        <td class="p-2.5 text-right">
                                            <button @click="dropColumn(col.name)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-rose-400" title="Drop Column">
                                                <i data-lucide="trash" class="w-3 h-3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Indexes Section -->
                        <div v-if="tableIndexes.length > 0" class="space-y-2">
                            <h4 class="text-sm font-semibold text-slate-200">Indexes (@{{ tableIndexes.length }})</h4>
                            <div class="rounded-lg border border-slate-800 overflow-hidden">
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th class="p-2.5">Key Name</th>
                                            <th class="p-2.5">Columns</th>
                                            <th class="p-2.5">Type</th>
                                            <th class="p-2.5">Unique</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                        <tr v-for="idx in tableIndexes" :key="idx.name">
                                            <td class="p-2.5 text-slate-200 font-semibold">@{{ idx.name }}</td>
                                            <td class="p-2.5 text-teal-400">@{{ (idx.columns || []).join(', ') }}</td>
                                            <td class="p-2.5 text-slate-400">@{{ idx.type }}</td>
                                            <td class="p-2.5">
                                                <span :class="idx.unique ? 'text-emerald-400' : 'text-slate-500'">@{{ idx.unique ? 'YES' : 'NO' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Foreign Keys Section -->
                        <div v-if="tableForeignKeys.length > 0" class="space-y-2">
                            <h4 class="text-sm font-semibold text-slate-200">Foreign Keys (@{{ tableForeignKeys.length }})</h4>
                            <div class="rounded-lg border border-slate-800 overflow-hidden">
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th class="p-2.5">Constraint</th>
                                            <th class="p-2.5">Column</th>
                                            <th class="p-2.5">References</th>
                                            <th class="p-2.5">On Delete</th>
                                            <th class="p-2.5">On Update</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                        <tr v-for="fk in tableForeignKeys" :key="fk.name">
                                            <td class="p-2.5 text-slate-200">@{{ fk.name }}</td>
                                            <td class="p-2.5 text-teal-400">@{{ fk.column }}</td>
                                            <td class="p-2.5 text-cyan-400">@{{ fk.foreign_table }}.@{{ fk.foreign_column }}</td>
                                            <td class="p-2.5 text-slate-400">@{{ fk.on_delete }}</td>
                                            <td class="p-2.5 text-slate-400">@{{ fk.on_update }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SQL DDL Section -->
                        <div v-if="tableCreateSql" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-slate-200">Create Table SQL (DDL)</h4>
                                <button @click="copyToClipboard(tableCreateSql)" class="px-2 py-0.5 text-xs rounded border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300">Copy DDL</button>
                            </div>
                            <pre class="p-4 rounded-lg bg-slate-900 border border-slate-800 text-teal-300 font-mono text-xs overflow-x-auto whitespace-pre-wrap">@{{ tableCreateSql }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: SQL CONSOLE -->
            <div v-if="activeMainTab === 'query'" class="flex-1 flex flex-col overflow-hidden p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-teal-400"></i>
                        <span class="text-sm font-bold text-slate-200">SQL Query Console</span>
                        <span class="text-xs text-slate-500 font-mono">(@{{ activeConnection }})</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="runQuery" class="px-3 py-1.5 text-xs rounded-md bg-teal-600 hover:bg-teal-500 text-white font-semibold transition flex items-center space-x-1.5 shadow">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                            <span>Execute (Ctrl + Enter)</span>
                        </button>
                        <button @click="explainQuery" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300">
                            Explain
                        </button>
                        <button @click="clearSql" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-400">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Query Editor -->
                <div class="border border-slate-800 rounded-lg overflow-hidden shrink-0">
                    <textarea id="sql-editor" v-model="sqlQuery"></textarea>
                </div>

                <!-- Query Execution Results -->
                <div class="flex-1 flex flex-col overflow-hidden border border-slate-800 rounded-lg bg-slate-900/50">
                    <!-- Status Header -->
                    <div class="px-4 py-2 border-b border-slate-800 bg-slate-900 flex items-center justify-between text-xs font-mono">
                        <span class="text-slate-400">Results:</span>
                        <div v-if="queryResult" class="flex items-center space-x-4">
                            <span v-if="queryResult.success" class="text-emerald-400">
                                ✓ Query OK (@{{ queryResult.execution_time_ms }} ms) — @{{ queryResult.is_select ? queryResult.rows_count + ' rows' : queryResult.affected_rows + ' affected' }}
                            </span>
                            <span v-else class="text-rose-400">
                                ✗ Error (@{{ queryResult.execution_time_ms }} ms)
                            </span>
                        </div>
                    </div>

                    <!-- Results Output -->
                    <div class="flex-1 overflow-auto custom-scrollbar">
                        <div v-if="!queryResult" class="text-center py-16 text-slate-500 text-xs">
                            Enter an SQL query above and click <strong class="text-slate-400">Execute</strong> or press <kbd class="px-1 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]">Ctrl + Enter</kbd>.
                        </div>
                        <div v-else-if="!queryResult.success" class="p-4 text-xs font-mono text-rose-300 bg-rose-950/40 whitespace-pre-wrap">
                            @{{ queryResult.error }}
                        </div>
                        <table v-else-if="queryResult.is_select" class="w-full text-left text-xs font-mono border-collapse">
                            <thead class="bg-slate-900 sticky top-0 border-b border-slate-800 text-slate-400">
                                <tr>
                                    <th v-for="col in queryResult.columns" :key="col" class="p-2.5 font-semibold text-slate-200">
                                        @{{ col }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                <tr v-for="(row, idx) in queryResult.rows" :key="idx" class="hover:bg-slate-900/60">
                                    <td v-for="col in queryResult.columns" :key="col" class="p-2.5 text-slate-300 max-w-xs truncate border-r border-slate-900">
                                        @{{ row[col] !== null ? formatCellValue(row[col]) : 'NULL' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-8 text-center text-xs text-emerald-400 font-mono">
                            Statement executed successfully. Affected rows: <strong>@{{ queryResult.affected_rows }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: DATABASE INFO & METRICS -->
            <div v-if="activeMainTab === 'info'" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-100">Database Server Information</h3>
                    <p class="text-xs text-slate-400">Metadata and connection configuration for active database.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="(val, key) in systemInfo" :key="key" class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 space-y-1">
                        <span class="text-xs font-mono uppercase tracking-wider text-slate-500">@{{ key }}</span>
                        <div class="text-sm font-semibold font-mono text-slate-200 truncate">@{{ val || '-' }}</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-slate-200">Tables Overview (@{{ tables.length }})</h4>
                    <div class="rounded-xl border border-slate-800 overflow-hidden">
                        <table class="w-full text-left text-xs font-mono">
                            <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="p-3">Table Name</th>
                                    <th class="p-3">Engine</th>
                                    <th class="p-3">Rows</th>
                                    <th class="p-3">Data Size</th>
                                    <th class="p-3">Index Size</th>
                                    <th class="p-3">Collation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                <tr v-for="t in tables" :key="t.name" class="hover:bg-slate-900/60">
                                    <td class="p-3 font-semibold text-teal-300">@{{ t.name }}</td>
                                    <td class="p-3 text-slate-400">@{{ t.engine }}</td>
                                    <td class="p-3 text-slate-200">@{{ formatNumber(t.rows_count) }}</td>
                                    <td class="p-3 text-slate-300">@{{ t.data_size || t.size }}</td>
                                    <td class="p-3 text-slate-400">@{{ t.index_size || '-' }}</td>
                                    <td class="p-3 text-slate-400">@{{ t.collation }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL 1: ADD DYNAMIC DATABASE CONNECTION -->
    <div v-if="showAddConnectionModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-teal-600/20 text-teal-400 flex items-center justify-center">
                        <i data-lucide="plug" class="w-4 h-4"></i>
                    </div>
                    <h3 class="font-bold text-sm text-slate-100">Connect to Database</h3>
                </div>
                <button @click="showAddConnectionModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Connection Alias *</label>
                    <input v-model="newConn.name" type="text" placeholder="e.g. analytics_db, production_replica" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Driver *</label>
                        <select v-model="newConn.driver" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                            <option value="mysql">MySQL / MariaDB</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                            <option value="sqlsrv">SQL Server</option>
                        </select>
                    </div>
                    <div v-if="newConn.driver !== 'sqlite'">
                        <label class="block text-slate-400 mb-1">Host & Port</label>
                        <div class="flex space-x-2">
                            <input v-model="newConn.host" type="text" placeholder="127.0.0.1" class="w-2/3 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                            <input v-model="newConn.port" type="text" :placeholder="newConn.driver === 'pgsql' ? '5432' : '3306'" class="w-1/3 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">@{{ newConn.driver === 'sqlite' ? 'Database File Path *' : 'Database Name *' }}</label>
                    <input v-model="newConn.database" type="text" :placeholder="newConn.driver === 'sqlite' ? '/path/to/database.sqlite' : 'my_database'" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                </div>

                <div v-if="newConn.driver !== 'sqlite'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Username</label>
                        <input v-model="newConn.username" type="text" placeholder="root" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Password</label>
                        <input v-model="newConn.password" type="password" placeholder="••••••••" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-800 pt-4">
                <button @click="testDynamicConnection" :disabled="testingConnection" class="px-3 py-1.5 text-xs rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center space-x-1.5">
                    <span v-if="testingConnection" class="animate-spin text-teal-400">⟳</span>
                    <span>Test Connection</span>
                </button>
                <div class="flex items-center space-x-2">
                    <button @click="showAddConnectionModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                    <button @click="saveDynamicConnection" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium shadow-md">Connect</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: INSERT / EDIT ROW -->
    <div v-if="showRowModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 shrink-0">
                <h3 class="font-bold text-sm text-slate-100">@{{ isEditingRow ? 'Edit Record' : 'Insert New Record' }} into @{{ selectedTableName }}</h3>
                <button @click="showRowModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 text-xs pr-1">
                <div v-for="col in tableColumns" :key="col.name" class="p-3 rounded-lg bg-slate-800/40 border border-slate-800 space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="font-mono text-slate-300 font-semibold">@{{ col.name }}</label>
                        <div class="flex items-center space-x-2 text-[10px] text-slate-500 font-mono">
                            <span>@{{ col.full_type || col.type }}</span>
                            <label v-if="col.nullable" class="flex items-center space-x-1 cursor-pointer">
                                <input type="checkbox" v-model="rowNullFlags[col.name]" class="rounded bg-slate-800 border-slate-700 text-teal-600 focus:ring-0">
                                <span>Set NULL</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="!rowNullFlags[col.name]">
                        <textarea v-if="col.type === 'json' || col.type === 'text' || col.type === 'longtext'" v-model="activeRowData[col.name]" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-2 text-slate-200 font-mono focus:outline-none focus:border-teal-500"></textarea>
                        <input v-else v-model="activeRowData[col.name]" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-1.5 text-slate-200 font-mono focus:outline-none focus:border-teal-500">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4 shrink-0">
                <button @click="showRowModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="saveRowData" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium">Save Record</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: CREATE TABLE -->
    <div v-if="showCreateTableModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-3xl w-full p-6 space-y-4 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 shrink-0">
                <h3 class="font-bold text-sm text-slate-100">Create New Table</h3>
                <button @click="showCreateTableModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-4 shrink-0 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Table Name *</label>
                    <input v-model="newTable.name" type="text" placeholder="e.g. orders, user_profiles" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar space-y-2 pr-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-300">Columns</span>
                    <button @click="addNewTableColumn" class="px-2 py-1 text-[11px] rounded bg-slate-800 hover:bg-slate-700 text-teal-400 border border-slate-700 flex items-center space-x-1">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>Add Column</span>
                    </button>
                </div>

                <div v-for="(col, cIdx) in newTable.columns" :key="cIdx" class="grid grid-cols-12 gap-2 p-2.5 rounded-lg bg-slate-800/60 border border-slate-700/60 text-xs items-center font-mono">
                    <input v-model="col.name" type="text" placeholder="Column name" class="col-span-3 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-200">
                    <select v-model="col.type" class="col-span-3 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-200">
                        <option value="INT">INT / BIGINT</option>
                        <option value="VARCHAR">VARCHAR</option>
                        <option value="TEXT">TEXT</option>
                        <option value="DATETIME">DATETIME</option>
                        <option value="TIMESTAMP">TIMESTAMP</option>
                        <option value="DECIMAL">DECIMAL</option>
                        <option value="BOOLEAN">BOOLEAN</option>
                        <option value="JSON">JSON</option>
                    </select>
                    <input v-model="col.length" type="text" placeholder="Length (255)" class="col-span-2 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-200">
                    <label class="col-span-2 flex items-center space-x-1 text-[11px] text-slate-400 font-sans">
                        <input type="checkbox" v-model="col.primary" class="rounded bg-slate-800 border-slate-700 text-teal-600">
                        <span>PK</span>
                    </label>
                    <button @click="removeNewTableColumn(cIdx)" class="col-span-2 text-right text-slate-500 hover:text-rose-400">Remove</button>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4 shrink-0">
                <button @click="showCreateTableModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitCreateTable" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium">Create Table</button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: IMPORT SQL / CSV -->
    <div v-if="showImportModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-sm text-slate-100">Import SQL Script</h3>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Upload SQL Dump File</label>
                    <input type="file" ref="importFileInput" accept=".sql" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-slate-300 font-mono text-xs">
                </div>
                <div class="text-center text-slate-600 font-bold uppercase text-[10px]">— OR PASTE SQL —</div>
                <div>
                    <textarea v-model="importSqlText" rows="6" placeholder="Paste SQL commands here..." class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-slate-200 font-mono focus:outline-none focus:border-teal-500"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4">
                <button @click="showImportModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitImportSql" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium">Execute Import</button>
            </div>
        </div>
    </div>

    <!-- MODAL 5: ADD COLUMN -->
    <div v-if="showAddColumnModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-sm text-slate-100">Add Column to @{{ selectedTableName }}</h3>
                <button @click="showAddColumnModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Column Name *</label>
                    <input v-model="newColumn.name" type="text" placeholder="e.g. status, phone_number" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Data Type *</label>
                        <select v-model="newColumn.type" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="INT">INT</option>
                            <option value="BIGINT">BIGINT</option>
                            <option value="TEXT">TEXT</option>
                            <option value="DATETIME">DATETIME</option>
                            <option value="BOOLEAN">BOOLEAN</option>
                            <option value="DECIMAL">DECIMAL</option>
                            <option value="JSON">JSON</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Length / Values</label>
                        <input v-model="newColumn.length" type="text" placeholder="255" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">Default Value</label>
                    <input v-model="newColumn.default" type="text" placeholder="NULL or default value" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" v-model="newColumn.nullable" id="colNullable" class="rounded bg-slate-800 border-slate-700 text-teal-600">
                    <label for="colNullable" class="text-slate-300">Allow NULL</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4">
                <button @click="showAddColumnModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitAddColumn" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-medium">Add Column</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const { createApp, ref, computed, onMounted, nextTick } = Vue;

    createApp({
        setup() {
            const config = ref(@json($config));
            const activeConnection = ref(@json($activeConnection));
            const selectedConnection = ref(@json($activeConnection));
            const connections = ref(@json($connections));
            const systemInfo = ref(@json($systemInfo));
            const tables = ref(@json($tables));

            const activeMainTab = ref('tables'); // 'tables', 'query', 'info'
            const activeTableTab = ref('browse'); // 'browse', 'structure'
            const selectedTableName = ref(tables.value.length > 0 ? tables.value[0].name : '');
            const tableSearch = ref('');
            const isLoading = ref(false);
            const alertMessage = ref('');
            const alertType = ref('success');

            // Table Data Browser State
            const tableColumns = ref([]);
            const tablePrimaryKeys = ref([]);
            const tableRows = ref([]);
            const tableRowsTotal = ref(0);
            const currentPage = ref(1);
            const lastPage = ref(1);
            const perPage = ref(100);
            const sortCol = ref('');
            const sortDir = ref('asc');
            const dataSearch = ref('');
            const selectedRowKeys = ref([]);
            const showExportDropdown = ref(false);

            // Table Structure State
            const tableIndexes = ref([]);
            const tableForeignKeys = ref([]);
            const tableCreateSql = ref('');

            // SQL Console State
            const sqlQuery = ref('SELECT * FROM ' + (selectedTableName.value ? '`' + selectedTableName.value + '`' : 'users') + ' LIMIT 50;');
            const queryResult = ref(null);
            let codeEditorInstance = null;

            // Modals
            const showAddConnectionModal = ref(false);
            const testingConnection = ref(false);
            const newConn = ref({ name: '', driver: 'mysql', host: '127.0.0.1', port: '3306', database: '', username: 'root', password: '' });

            const showRowModal = ref(false);
            const isEditingRow = ref(false);
            const activeRowData = ref({});
            const rowNullFlags = ref({});
            const originalRowWhere = ref({});

            const showCreateTableModal = ref(false);
            const newTable = ref({
                name: '',
                columns: [
                    { name: 'id', type: 'INT', length: '11', primary: true, auto_increment: true, nullable: false },
                    { name: 'created_at', type: 'TIMESTAMP', length: '', primary: false, auto_increment: false, nullable: true },
                ]
            });

            const showAddColumnModal = ref(false);
            const newColumn = ref({ name: '', type: 'VARCHAR', length: '255', nullable: true, default: '' });

            const showImportModal = ref(false);
            const importSqlText = ref('');
            const importFileInput = ref(null);

            const filteredTables = computed(() => {
                if (!tableSearch.value) return tables.value;
                return tables.value.filter(t => t.name.toLowerCase().includes(tableSearch.value.toLowerCase()));
            });

            const isAllSelected = computed(() => {
                return tableRows.value.length > 0 && selectedRowKeys.value.length === tableRows.value.length;
            });

            const triggerLucide = () => {
                nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            };

            const showAlert = (msg, type = 'success') => {
                alertMessage.value = msg;
                alertType.value = type;
                setTimeout(() => {
                    if (alertMessage.value === msg) alertMessage.value = '';
                }, 6000);
            };

            const formatNumber = (num) => {
                return new Intl.NumberFormat().format(num || 0);
            };

            const formatCellValue = (val) => {
                if (val === null || val === undefined) return '';
                if (typeof val === 'object') return JSON.stringify(val);
                return String(val);
            };

            const getRowKey = (row) => {
                if (tablePrimaryKeys.value.length > 0) {
                    return tablePrimaryKeys.value.map(pk => `${pk}:${row[pk]}`).join('|');
                }
                return JSON.stringify(row);
            };

            const getRowWhere = (row) => {
                const where = {};
                if (tablePrimaryKeys.value.length > 0) {
                    tablePrimaryKeys.value.forEach(pk => {
                        where[pk] = row[pk];
                    });
                } else {
                    tableColumns.value.forEach(col => {
                        where[col.name] = row[col.name];
                    });
                }
                return where;
            };

            // Switch Database Connection
            const switchConnection = async () => {
                isLoading.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/connections/switch`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ connection: selectedConnection.value })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Failed to switch connection');

                    activeConnection.value = data.active;
                    systemInfo.value = data.system_info;
                    tables.value = data.tables;
                    selectedTableName.value = tables.value.length > 0 ? tables.value[0].name : '';
                    if (selectedTableName.value) {
                        await selectTable(selectedTableName.value);
                    }
                    showAlert(`Switched to connection: ${data.active}`);
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    isLoading.value = false;
                    triggerLucide();
                }
            };

            // Test Dynamic Connection
            const testDynamicConnection = async () => {
                testingConnection.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/connections/test`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(newConn.value)
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Connection failed');
                    showAlert('Connection successful!', 'success');
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    testingConnection.value = false;
                }
            };

            // Save Dynamic Connection
            const saveDynamicConnection = async () => {
                try {
                    const res = await fetch(`/${config.value.path}/api/connections`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(newConn.value)
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Failed to add connection');

                    connections.value = data.connections;
                    activeConnection.value = data.name;
                    selectedConnection.value = data.name;
                    systemInfo.value = data.system_info;
                    tables.value = data.tables;
                    showAddConnectionModal.value = false;
                    showAlert(data.message);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // Select and load table
            const selectTable = async (tbl) => {
                selectedTableName.value = tbl;
                selectedRowKeys.value = [];
                await Promise.all([
                    fetchTableStructure(tbl),
                    fetchTableRows(1)
                ]);
                triggerLucide();
            };

            // Fetch Structure (Columns, Indexes, FKs)
            const fetchTableStructure = async (tbl) => {
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${tbl}`);
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    tableColumns.value = data.columns;
                    tableIndexes.value = data.indexes;
                    tableForeignKeys.value = data.foreign_keys;
                    tableCreateSql.value = data.create_sql;
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // Fetch Table Rows
            const fetchTableRows = async (page = 1) => {
                if (!selectedTableName.value) return;
                isLoading.value = true;
                try {
                    const params = new URLSearchParams({
                        page,
                        per_page: perPage.value,
                        search: dataSearch.value,
                        sort_col: sortCol.value,
                        sort_dir: sortDir.value,
                    });
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows?${params}`);
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    tableRows.value = data.rows;
                    tableRowsTotal.value = data.total;
                    currentPage.value = data.page;
                    lastPage.value = data.last_page;
                    tableColumns.value = data.columns;
                    tablePrimaryKeys.value = data.primary_keys;
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    isLoading.value = false;
                    triggerLucide();
                }
            };

            const sortBy = (col) => {
                if (sortCol.value === col) {
                    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
                } else {
                    sortCol.value = col;
                    sortDir.value = 'asc';
                }
                fetchTableRows(1);
            };

            const toggleSelectAllRows = () => {
                if (isAllSelected.value) {
                    selectedRowKeys.value = [];
                } else {
                    selectedRowKeys.value = tableRows.value.map(r => getRowKey(r));
                }
            };

            // Insert / Edit Modals
            const openInsertModal = () => {
                isEditingRow.value = false;
                activeRowData.value = {};
                rowNullFlags.value = {};
                tableColumns.value.forEach(col => {
                    activeRowData.value[col.name] = col.default || '';
                    rowNullFlags.value[col.name] = col.nullable && col.default === null;
                });
                showRowModal.value = true;
            };

            const openEditModal = (row) => {
                isEditingRow.value = true;
                activeRowData.value = { ...row };
                originalRowWhere.value = getRowWhere(row);
                rowNullFlags.value = {};
                tableColumns.value.forEach(col => {
                    rowNullFlags.value[col.name] = row[col.name] === null;
                });
                showRowModal.value = true;
            };

            const saveRowData = async () => {
                const payload = {};
                tableColumns.value.forEach(col => {
                    if (rowNullFlags.value[col.name]) {
                        payload[col.name] = null;
                    } else {
                        payload[col.name] = activeRowData.value[col.name];
                    }
                });

                try {
                    let res;
                    if (isEditingRow.value) {
                        res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ where: originalRowWhere.value, data: payload })
                        });
                    } else {
                        res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ data: payload })
                        });
                    }

                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    showRowModal.value = false;
                    showAlert(data.message);
                    await fetchTableRows(currentPage.value);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const deleteSingleRow = async (row) => {
                if (!confirm('Are you sure you want to delete this record?')) return;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ where: getRowWhere(row) })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    showAlert(data.message);
                    await fetchTableRows(currentPage.value);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const deleteSelectedRows = async () => {
                if (!confirm(`Delete ${selectedRowKeys.value.length} selected records?`)) return;
                const whereList = tableRows.value
                    .filter(r => selectedRowKeys.value.includes(getRowKey(r)))
                    .map(r => getRowWhere(r));

                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows/bulk-delete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ where_list: whereList })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    selectedRowKeys.value = [];
                    showAlert(data.message);
                    await fetchTableRows(currentPage.value);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const duplicateRecord = async (row) => {
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows/duplicate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ where: getRowWhere(row) })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    showAlert(data.message);
                    await fetchTableRows(currentPage.value);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // Schema & Table Operations
            const truncateCurrentTable = async () => {
                if (!confirm(`Are you sure you want to TRUNCATE table [${selectedTableName.value}]? All records will be wiped.`)) return;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/truncate`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    showAlert(data.message);
                    await fetchTableRows(1);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const dropCurrentTable = async () => {
                if (!confirm(`Are you sure you want to DROP table [${selectedTableName.value}]? This cannot be undone.`)) return;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    tables.value = data.tables;
                    selectedTableName.value = tables.value.length > 0 ? tables.value[0].name : '';
                    if (selectedTableName.value) await selectTable(selectedTableName.value);
                    showAlert(data.message);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const addNewTableColumn = () => {
                newTable.value.columns.push({ name: '', type: 'VARCHAR', length: '255', primary: false, auto_increment: false, nullable: true });
            };

            const removeNewTableColumn = (idx) => {
                newTable.value.columns.splice(idx, 1);
            };

            const submitCreateTable = async () => {
                try {
                    const res = await fetch(`/${config.value.path}/api/tables`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            table: newTable.value.name,
                            columns: newTable.value.columns,
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    tables.value = data.tables;
                    showCreateTableModal.value = false;
                    showAlert(data.message);
                    await selectTable(newTable.value.name);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const submitAddColumn = async () => {
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/columns`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ column: newColumn.value })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    tableColumns.value = data.columns;
                    showAddColumnModal.value = false;
                    showAlert(data.message);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const dropColumn = async (col) => {
                if (!confirm(`Are you sure you want to drop column [${col}]?`)) return;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/columns/${col}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    tableColumns.value = data.columns;
                    showAlert(data.message);
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // SQL Console Execution
            const initCodeMirror = () => {
                const textarea = document.getElementById('sql-editor');
                if (textarea && !codeEditorInstance) {
                    codeEditorInstance = CodeMirror.fromTextArea(textarea, {
                        mode: 'text/x-sql',
                        theme: 'dracula',
                        lineNumbers: true,
                        lineWrapping: true,
                        extraKeys: {
                            'Ctrl-Enter': () => runQuery(),
                            'Cmd-Enter': () => runQuery(),
                        }
                    });
                    codeEditorInstance.setValue(sqlQuery.value);
                    codeEditorInstance.on('change', () => {
                        sqlQuery.value = codeEditorInstance.getValue();
                    });
                }
            };

            const runQuery = async () => {
                const sql = codeEditorInstance ? codeEditorInstance.getValue() : sqlQuery.value;
                if (!sql.trim()) return;

                isLoading.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/query/execute`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ sql })
                    });
                    const data = await res.json();
                    queryResult.value = data;
                } catch (e) {
                    queryResult.value = { success: false, error: e.message, execution_time_ms: 0 };
                } finally {
                    isLoading.value = false;
                }
            };

            const explainQuery = async () => {
                const sql = codeEditorInstance ? codeEditorInstance.getValue() : sqlQuery.value;
                if (!sql.trim()) return;

                isLoading.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/query/explain`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ sql })
                    });
                    const data = await res.json();
                    queryResult.value = { ...data, is_select: true, execution_time_ms: 0 };
                } catch (e) {
                    queryResult.value = { success: false, error: e.message, execution_time_ms: 0 };
                } finally {
                    isLoading.value = false;
                }
            };

            const clearSql = () => {
                if (codeEditorInstance) codeEditorInstance.setValue('');
                sqlQuery.value = '';
                queryResult.value = null;
            };

            const submitImportSql = async () => {
                const formData = new FormData();
                if (importFileInput.value && importFileInput.value.files[0]) {
                    formData.append('file', importFileInput.value.files[0]);
                } else if (importSqlText.value.trim()) {
                    formData.append('sql', importSqlText.value);
                } else {
                    showAlert('Please choose an SQL file or paste SQL text.', 'error');
                    return;
                }

                try {
                    const res = await fetch(`/${config.value.path}/api/import/sql`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    showImportModal.value = false;
                    showAlert(`Import completed: ${data.result.executed} statement(s) executed.`);
                    await refreshAll();
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const copyToClipboard = (text) => {
                navigator.clipboard.writeText(text);
                showAlert('Copied to clipboard!');
            };

            const refreshAll = async () => {
                isLoading.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables`);
                    const data = await res.json();
                    tables.value = data.tables;
                    systemInfo.value = data.system_info;
                    if (selectedTableName.value) {
                        await selectTable(selectedTableName.value);
                    }
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    isLoading.value = false;
                    triggerLucide();
                }
            };

            onMounted(() => {
                triggerLucide();
                if (selectedTableName.value) {
                    selectTable(selectedTableName.value);
                }
                nextTick(() => {
                    initCodeMirror();
                });
            });

            return {
                config,
                activeConnection,
                selectedConnection,
                connections,
                systemInfo,
                tables,
                filteredTables,
                selectedTableName,
                tableSearch,
                activeMainTab,
                activeTableTab,
                tableColumns,
                tablePrimaryKeys,
                tableRows,
                tableRowsTotal,
                currentPage,
                lastPage,
                perPage,
                sortCol,
                sortDir,
                dataSearch,
                selectedRowKeys,
                isAllSelected,
                tableIndexes,
                tableForeignKeys,
                tableCreateSql,
                sqlQuery,
                queryResult,
                isLoading,
                alertMessage,
                alertType,
                showExportDropdown,
                showAddConnectionModal,
                testingConnection,
                newConn,
                showRowModal,
                isEditingRow,
                activeRowData,
                rowNullFlags,
                showCreateTableModal,
                newTable,
                showAddColumnModal,
                newColumn,
                showImportModal,
                importSqlText,
                importFileInput,
                formatNumber,
                formatCellValue,
                getRowKey,
                switchConnection,
                testDynamicConnection,
                saveDynamicConnection,
                selectTable,
                fetchTableRows,
                sortBy,
                toggleSelectAllRows,
                openInsertModal,
                openEditModal,
                saveRowData,
                deleteSingleRow,
                deleteSelectedRows,
                duplicateRecord,
                truncateCurrentTable,
                dropCurrentTable,
                addNewTableColumn,
                removeNewTableColumn,
                submitCreateTable,
                submitAddColumn,
                dropColumn,
                runQuery,
                explainQuery,
                clearSql,
                submitImportSql,
                copyToClipboard,
                refreshAll,
            };
        }
    }).mount('#laramyadmin-app');
</script>
@endpush
