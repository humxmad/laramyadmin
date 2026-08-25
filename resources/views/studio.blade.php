@extends('laramyadmin::layout')

@section('content')
<div id="laramyadmin-app" class="flex flex-col h-screen overflow-hidden bg-slate-950 text-slate-100 selection:bg-teal-500 selection:text-white" v-cloak>
    <!-- Top Navigation Bar -->
    <header class="h-14 border-b border-slate-800/90 bg-slate-900/90 backdrop-blur-md px-4 flex items-center justify-between shrink-0 z-20 shadow-sm">
        <div class="flex items-center space-x-4">
            <!-- Brand Logo -->
            <div class="flex items-center space-x-2.5 select-none cursor-pointer" @click="setMainTab('tables')">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 via-emerald-500 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-500/20 ring-1 ring-white/20">
                    <i data-lucide="database" class="w-4 h-4 text-slate-950 stroke-[2.5]"></i>
                </div>
                <div class="flex items-center">
                    <span class="font-bold text-base tracking-tight bg-gradient-to-r from-teal-200 via-emerald-200 to-cyan-300 bg-clip-text text-transparent">LaraMyAdmin</span>
                    <span class="text-[10px] uppercase font-mono tracking-wider ml-2 px-1.5 py-0.5 rounded-full bg-teal-950 border border-teal-700/60 text-teal-300 font-semibold shadow-inner">Studio Pro</span>
                </div>
            </div>

            <!-- Connection Switcher -->
            <div class="relative flex items-center">
                <div class="flex items-center bg-slate-800/90 border border-slate-700/80 rounded-lg p-1 space-x-1 shadow-inner">
                    <div class="flex items-center px-2 py-0.5 space-x-1.5 text-xs">
                        <span class="w-2 h-2 rounded-full" :class="activeConnection ? 'bg-emerald-400 ring-2 ring-emerald-400/30 animate-pulse' : 'bg-rose-400'"></span>
                        <span class="text-slate-400 font-medium text-[11px]">DB:</span>
                    </div>
                    <select v-model="selectedConnection" @change="switchConnection" class="bg-slate-900 border border-slate-700 rounded-md text-xs px-2.5 py-1 text-slate-200 focus:outline-none focus:border-teal-500 cursor-pointer font-mono font-medium">
                        <option v-for="conn in connections" :key="conn.name" :value="conn.name">
                            @{{ conn.name }} (@{{ conn.driver }}) @{{ conn.is_default ? '★' : '' }} @{{ conn.is_dynamic ? '⚡' : '' }}
                        </option>
                    </select>
                    <button @click="showAddConnectionModal = true" class="p-1.5 hover:bg-slate-700 rounded-md text-slate-300 hover:text-teal-300 transition" title="Add Connection">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Global Navigation Tabs & Actions -->
        <div class="flex items-center space-x-2">
            <!-- Read Only Badge -->
            <span v-if="config.readOnly" class="text-xs px-2.5 py-1 rounded-md bg-amber-950/80 border border-amber-700/60 text-amber-300 font-medium flex items-center space-x-1 shadow-sm">
                <i data-lucide="lock" class="w-3 h-3"></i>
                <span>Read-Only</span>
            </span>

            <!-- Top Nav Tabs -->
            <div class="flex items-center bg-slate-800/70 border border-slate-800 rounded-lg p-0.5 shadow-inner">
                <button @click="setMainTab('tables')" :class="activeMainTab === 'tables' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="table-2" class="w-3.5 h-3.5"></i>
                    <span>Tables</span>
                </button>
                <button @click="setMainTab('query')" :class="activeMainTab === 'query' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                    <span>SQL Console</span>
                </button>
                <button @click="setMainTab('erd')" :class="activeMainTab === 'erd' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="network" class="w-3.5 h-3.5"></i>
                    <span>ER Diagram</span>
                </button>
                <button @click="setMainTab('search')" :class="activeMainTab === 'search' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Global Search</span>
                </button>
                <button @click="setMainTab('diff')" :class="activeMainTab === 'diff' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="git-compare" class="w-3.5 h-3.5"></i>
                    <span>Schema Diff</span>
                </button>
                <button @click="setMainTab('info')" :class="activeMainTab === 'info' ? 'bg-teal-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1.5 text-xs rounded-md transition flex items-center space-x-1.5">
                    <i data-lucide="server" class="w-3.5 h-3.5"></i>
                    <span>DB Info</span>
                </button>
            </div>

            <!-- Import / Dump SQL -->
            <button @click="showImportModal = true" class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 transition flex items-center space-x-1.5 shadow-sm">
                <i data-lucide="upload" class="w-3.5 h-3.5 text-teal-400"></i>
                <span>Import</span>
            </button>
            <a :href="'/' + config.path + '/export/sql'" target="_blank" class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200 transition flex items-center space-x-1.5 shadow-sm">
                <i data-lucide="download" class="w-3.5 h-3.5 text-teal-400"></i>
                <span>Dump SQL</span>
            </a>

            <!-- Refresh -->
            <button @click="refreshAll" class="p-2 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 transition" title="Refresh All Data">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="isLoading ? 'animate-spin text-teal-400' : ''"></i>
            </button>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar (Tables list) -->
        <aside class="w-64 border-r border-slate-800/90 bg-slate-900/60 flex flex-col shrink-0 select-none">
            <!-- Sidebar Header & Search -->
            <div class="p-3 border-b border-slate-800/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tables & Views</span>
                    <button @click="showCreateTableModal = true" class="px-2 py-0.5 text-[11px] rounded bg-teal-900/70 hover:bg-teal-800 border border-teal-700/60 text-teal-300 font-semibold transition flex items-center space-x-1 shadow-sm">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>New Table</span>
                    </button>
                </div>
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-500"></i>
                    <input v-model="tableSearch" type="text" placeholder="Filter tables..." class="w-full bg-slate-800/90 border border-slate-700 rounded-md pl-8 pr-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-teal-500 font-mono shadow-inner">
                </div>
            </div>

            <!-- Tables List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-0.5">
                <div v-if="filteredTables.length === 0" class="text-center py-8 text-xs text-slate-500">
                    No matching tables found.
                </div>
                <div v-for="t in filteredTables" :key="t.name" 
                     @click="setMainTab('tables'); selectTable(t.name);"
                     :class="selectedTableName === t.name && activeMainTab === 'tables' ? 'bg-teal-950/90 border-teal-700/80 text-teal-300 shadow' : 'border-transparent text-slate-300 hover:bg-slate-800/60'"
                     class="group flex items-center justify-between px-2.5 py-1.5 rounded-md border text-xs cursor-pointer transition">
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
            <div class="p-3 border-t border-slate-800 bg-slate-900/90 text-[11px] text-slate-400 space-y-1.5 font-mono">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Driver:</span>
                    <span class="px-1.5 py-0.5 rounded bg-slate-800 text-teal-300 font-semibold text-[10px]">@{{ systemInfo.Driver || '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Database:</span>
                    <span class="text-slate-200 truncate max-w-[120px]" :title="systemInfo.Database">@{{ systemInfo.Database || '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">DB Size:</span>
                    <span class="text-emerald-400 font-semibold">@{{ systemInfo['Database Size'] || '-' }}</span>
                </div>
            </div>
        </aside>

        <!-- Main Canvas -->
        <main class="flex-1 flex flex-col overflow-hidden bg-slate-950">
            <!-- TAB 1: TABLE MANAGER (BROWSE / STRUCTURE) -->
            <div v-if="activeMainTab === 'tables'" class="flex-1 flex flex-col overflow-hidden">
                <div v-if="!selectedTableName" class="flex-1 flex flex-col items-center justify-center text-slate-500 p-8 space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600 shadow-inner">
                        <i data-lucide="layers" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-300">No Table Selected</h3>
                    <p class="text-xs text-slate-500 max-w-sm text-center">Select a table from the sidebar to browse records, modify table schema, or run operations.</p>
                </div>

                <div v-else class="flex-1 flex flex-col overflow-hidden">
                    <!-- Table Top Header -->
                    <div class="px-4 py-2.5 border-b border-slate-800 bg-slate-900/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="table" class="w-4 h-4 text-teal-400"></i>
                                <span class="font-mono text-sm font-bold text-slate-100">@{{ selectedTableName }}</span>
                            </div>
                            <!-- Sub Tabs (Browse / Structure) -->
                            <div class="flex items-center bg-slate-800/80 border border-slate-700/80 rounded-lg p-0.5 text-xs">
                                <button @click="setTableTab('browse')" :class="activeTableTab === 'browse' ? 'bg-teal-600 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1 rounded transition">
                                    Browse Data (@{{ formatNumber(tableRowsTotal) }})
                                </button>
                                <button @click="setTableTab('structure')" :class="activeTableTab === 'structure' ? 'bg-teal-600 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1 rounded transition">
                                    Structure & Schema
                                </button>
                            </div>
                        </div>

                        <!-- Table Actions Toolbar -->
                        <div class="flex items-center space-x-2">
                            <!-- Insert Row -->
                            <button v-if="activeTableTab === 'browse'" @click="openInsertModal" class="px-2.5 py-1 text-xs rounded-md bg-teal-600 hover:bg-teal-500 text-white font-semibold transition flex items-center space-x-1 shadow-sm">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Insert</span>
                            </button>

                            <!-- Mock Data Button -->
                            <button @click="showMockDataModal = true" class="px-2.5 py-1 text-xs rounded-md border border-purple-800 bg-purple-950/60 hover:bg-purple-900/80 text-purple-300 font-semibold transition flex items-center space-x-1 shadow-sm">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-purple-400"></i>
                                <span>Mock Data</span>
                            </button>

                            <!-- Laravel Code Generator -->
                            <button @click="openCodeGeneratorModal" class="px-2.5 py-1 text-xs rounded-md border border-rose-800 bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 font-semibold transition flex items-center space-x-1 shadow-sm">
                                <i data-lucide="code-2" class="w-3.5 h-3.5 text-rose-400"></i>
                                <span>Laravel Export</span>
                            </button>

                            <!-- Bulk Delete -->
                            <button v-if="activeTableTab === 'browse' && selectedRowKeys.length > 0" @click="deleteSelectedRows" class="px-2.5 py-1 text-xs rounded-md bg-rose-600 hover:bg-rose-500 text-white font-semibold transition flex items-center space-x-1 shadow">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Delete (@{{ selectedRowKeys.length }})</span>
                            </button>
                            
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button @click="showExportDropdown = !showExportDropdown" class="px-2.5 py-1 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 transition flex items-center space-x-1">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-teal-400"></i>
                                    <span>Export</span>
                                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                </button>
                                <div v-if="showExportDropdown" @click="showExportDropdown = false" class="absolute right-0 mt-1 w-36 bg-slate-900 border border-slate-700 rounded-lg shadow-2xl py-1 z-30 text-xs">
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
                        <div class="px-4 py-2 border-b border-slate-800 bg-slate-900/30 flex flex-wrap items-center justify-between gap-2 shrink-0">
                            <!-- Search & Quick Filter -->
                            <div class="flex items-center space-x-2">
                                <div class="relative w-64">
                                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-500"></i>
                                    <input v-model="dataSearch" @keyup.enter="fetchTableRows(1)" type="text" placeholder="Search rows..." class="w-full bg-slate-800/90 border border-slate-700 rounded-md pl-8 pr-2.5 py-1 text-xs text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                                </div>
                                <button @click="fetchTableRows(1)" class="px-3 py-1 text-xs rounded-md bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 font-medium">Search</button>
                                <button v-if="dataSearch" @click="dataSearch = ''; fetchTableRows(1)" class="text-xs text-slate-500 hover:text-slate-300">Clear</button>
                            </div>

                            <!-- Pagination Controls Top -->
                            <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
                                <span>Showing @{{ tableRows.length }} of @{{ formatNumber(tableRowsTotal) }}</span>
                                <span class="mx-1">|</span>
                                <span>Per page:</span>
                                <select v-model="perPage" @change="fetchTableRows(1)" class="bg-slate-800 border border-slate-700 rounded px-2 py-0.5 text-xs text-slate-200">
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                    <option :value="250">250</option>
                                </select>
                                <span class="mx-1">|</span>
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

                        <!-- Data Grid Table with Inline Cell Editing -->
                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <table class="w-full text-left text-xs border-collapse font-mono">
                                <thead class="bg-slate-900/95 text-slate-400 sticky top-0 z-10 border-b border-slate-800 shadow-sm backdrop-blur">
                                    <tr>
                                        <th class="p-2.5 w-10 text-center">
                                            <input type="checkbox" @change="toggleSelectAllRows" :checked="isAllSelected" class="rounded bg-slate-800 border-slate-700 text-teal-600 focus:ring-0 cursor-pointer">
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
                                        <td :colspan="tableColumns.length + 2" class="text-center py-16 text-slate-500">
                                            No rows found in this table. Click <strong>Insert</strong> or <strong>Mock Data</strong> to generate records.
                                        </td>
                                    </tr>
                                    <tr v-for="(row, rIndex) in tableRows" :key="rIndex" class="hover:bg-slate-900/70 transition group">
                                        <!-- Checkbox -->
                                        <td class="p-2.5 text-center">
                                            <input type="checkbox" :value="getRowKey(row)" v-model="selectedRowKeys" class="rounded bg-slate-800 border-slate-700 text-teal-600 focus:ring-0 cursor-pointer">
                                        </td>
                                        <!-- Actions -->
                                        <td class="p-2.5 text-center">
                                            <div class="flex items-center justify-center space-x-1 opacity-70 group-hover:opacity-100 transition">
                                                <button @click="openEditModal(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-teal-300 transition" title="Edit Record Modal">
                                                    <i data-lucide="pencil" class="w-3 h-3"></i>
                                                </button>
                                                <button @click="duplicateRecord(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-cyan-300 transition" title="Clone Record">
                                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                                </button>
                                                <button @click="deleteSingleRow(row)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-rose-400 transition" title="Delete Record">
                                                    <i data-lucide="trash" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <!-- Cells (with Spreadsheet Inline Editing on Double-Click) -->
                                        <td v-for="col in tableColumns" :key="col.name" 
                                            @dblclick="startInlineEdit(rIndex, col.name, row[col.name])"
                                            class="p-2.5 text-slate-300 max-w-xs truncate border-r border-slate-900/80 cursor-pointer hover:bg-teal-950/30 transition relative" 
                                            :title="'Double-click to edit. ' + formatCellValue(row[col.name])">
                                            
                                            <!-- Inline Edit Mode -->
                                            <div v-if="inlineEditing.rowIndex === rIndex && inlineEditing.colName === col.name" class="absolute inset-0 p-1 bg-slate-900 z-20 flex items-center">
                                                <input v-model="inlineEditing.value" 
                                                       @keyup.enter="saveInlineEdit(row)" 
                                                       @keyup.esc="cancelInlineEdit"
                                                       ref="inlineInput"
                                                       type="text" 
                                                       class="w-full h-full bg-slate-950 border border-teal-500 rounded px-2 text-xs text-teal-300 font-mono focus:outline-none shadow-lg">
                                            </div>

                                            <!-- Display Mode -->
                                            <span v-else-if="row[col.name] === null" class="text-slate-600 italic">NULL</span>
                                            <span v-else-if="typeof row[col.name] === 'boolean'" class="px-1.5 py-0.5 rounded text-[10px] font-semibold" :class="row[col.name] ? 'bg-emerald-950 text-emerald-400' : 'bg-rose-950 text-rose-400'">
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
                            <div>
                                <h4 class="text-sm font-bold text-slate-200">Table Columns (@{{ tableColumns.length }})</h4>
                                <p class="text-xs text-slate-500">Field names, types, nullability, and keys for @{{ selectedTableName }}.</p>
                            </div>
                            <button @click="showAddColumnModal = true" class="px-3 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold flex items-center space-x-1.5 shadow">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Column</span>
                            </button>
                        </div>

                        <!-- Columns Table -->
                        <div class="rounded-xl border border-slate-800 overflow-hidden shadow-sm">
                            <table class="w-full text-left text-xs font-mono">
                                <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="p-3">Column Name</th>
                                        <th class="p-3">Data Type</th>
                                        <th class="p-3">Nullable</th>
                                        <th class="p-3">Default</th>
                                        <th class="p-3">Key</th>
                                        <th class="p-3">Extra</th>
                                        <th class="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                    <tr v-for="col in tableColumns" :key="col.name" class="hover:bg-slate-900/50">
                                        <td class="p-3 font-bold text-slate-100 flex items-center space-x-1.5">
                                            <i v-if="col.primary" data-lucide="key" class="w-3 h-3 text-amber-400"></i>
                                            <span>@{{ col.name }}</span>
                                        </td>
                                        <td class="p-3 text-teal-400 font-semibold">@{{ col.full_type || col.type }}</td>
                                        <td class="p-3">
                                            <span :class="col.nullable ? 'text-emerald-400' : 'text-slate-500'">@{{ col.nullable ? 'YES' : 'NO' }}</span>
                                        </td>
                                        <td class="p-3 text-slate-400">@{{ col.default !== null ? col.default : 'NULL' }}</td>
                                        <td class="p-3">
                                            <span v-if="col.primary" class="px-1.5 py-0.5 rounded bg-amber-950 border border-amber-800 text-amber-400 text-[10px] font-bold">PRIMARY</span>
                                            <span v-else-if="col.unique" class="px-1.5 py-0.5 rounded bg-cyan-950 border border-cyan-800 text-cyan-400 text-[10px] font-bold">UNIQUE</span>
                                            <span v-else class="text-slate-600">-</span>
                                        </td>
                                        <td class="p-3 text-slate-400">@{{ col.extra || '-' }}</td>
                                        <td class="p-3 text-right">
                                            <button @click="dropColumn(col.name)" class="p-1 hover:bg-slate-800 rounded text-slate-400 hover:text-rose-400 transition" title="Drop Column">
                                                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Indexes Section -->
                        <div v-if="tableIndexes.length > 0" class="space-y-2">
                            <h4 class="text-sm font-bold text-slate-200">Indexes (@{{ tableIndexes.length }})</h4>
                            <div class="rounded-xl border border-slate-800 overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th class="p-3">Index Name</th>
                                            <th class="p-3">Indexed Columns</th>
                                            <th class="p-3">Type</th>
                                            <th class="p-3">Unique</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                        <tr v-for="idx in tableIndexes" :key="idx.name">
                                            <td class="p-3 text-slate-200 font-semibold">@{{ idx.name }}</td>
                                            <td class="p-3 text-teal-400">@{{ (idx.columns || []).join(', ') }}</td>
                                            <td class="p-3 text-slate-400">@{{ idx.type }}</td>
                                            <td class="p-3">
                                                <span :class="idx.unique ? 'text-emerald-400' : 'text-slate-500'">@{{ idx.unique ? 'YES' : 'NO' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Foreign Keys Section -->
                        <div v-if="tableForeignKeys.length > 0" class="space-y-2">
                            <h4 class="text-sm font-bold text-slate-200">Foreign Keys (@{{ tableForeignKeys.length }})</h4>
                            <div class="rounded-xl border border-slate-800 overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-slate-900 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th class="p-3">Constraint</th>
                                            <th class="p-3">Local Column</th>
                                            <th class="p-3">Referenced Table & Column</th>
                                            <th class="p-3">On Delete</th>
                                            <th class="p-3">On Update</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                        <tr v-for="fk in tableForeignKeys" :key="fk.name">
                                            <td class="p-3 text-slate-200 font-semibold">@{{ fk.name }}</td>
                                            <td class="p-3 text-teal-400">@{{ fk.column }}</td>
                                            <td class="p-3 text-cyan-300">@{{ fk.foreign_table }}.@{{ fk.foreign_column }}</td>
                                            <td class="p-3 text-slate-400">@{{ fk.on_delete }}</td>
                                            <td class="p-3 text-slate-400">@{{ fk.on_update }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SQL DDL Section -->
                        <div v-if="tableCreateSql" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-200">Create Table SQL (DDL)</h4>
                                <button @click="copyToClipboard(tableCreateSql)" class="px-2.5 py-1 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center space-x-1">
                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                    <span>Copy DDL</span>
                                </button>
                            </div>
                            <pre class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-teal-300 font-mono text-xs overflow-x-auto whitespace-pre-wrap">@{{ tableCreateSql }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: SQL CONSOLE (With Bookmarks & Saved Queries) -->
            <div v-if="activeMainTab === 'query'" class="flex-1 flex flex-col overflow-hidden p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-teal-400"></i>
                        <span class="text-sm font-bold text-slate-100">SQL Query Console</span>
                        <span class="text-xs text-slate-500 font-mono">(@{{ activeConnection }})</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Saved Queries / Bookmarks Dropdown -->
                        <button @click="showSavedQueriesModal = true" class="px-3 py-1.5 text-xs rounded-md border border-amber-700/80 bg-amber-950/60 hover:bg-amber-900/80 text-amber-300 font-semibold flex items-center space-x-1 shadow-sm">
                            <i data-lucide="bookmark" class="w-3.5 h-3.5"></i>
                            <span>Saved Queries (@{{ savedQueries.length }})</span>
                        </button>
                        <button @click="promptSaveQuery" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium" title="Save this query as a favorite bookmark">
                            <i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i>Save Query
                        </button>
                        
                        <!-- Execute -->
                        <button @click="runQuery" class="px-3.5 py-1.5 text-xs rounded-md bg-teal-600 hover:bg-teal-500 text-white font-semibold transition flex items-center space-x-1.5 shadow-md">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                            <span>Execute (Ctrl + Enter)</span>
                        </button>
                        <button @click="explainQuery" class="px-3 py-1.5 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300">
                            Explain
                        </button>
                        <button @click="clearSql" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-400">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Query Editor -->
                <div class="border border-slate-800 rounded-xl overflow-hidden shrink-0 shadow-inner">
                    <textarea id="sql-editor" v-model="sqlQuery"></textarea>
                </div>

                <!-- Query Execution Results -->
                <div class="flex-1 flex flex-col overflow-hidden border border-slate-800 rounded-xl bg-slate-900/50 shadow-sm">
                    <!-- Status Header -->
                    <div class="px-4 py-2 border-b border-slate-800 bg-slate-900 flex items-center justify-between text-xs font-mono">
                        <span class="text-slate-400 font-semibold">Results:</span>
                        <div v-if="queryResult" class="flex items-center space-x-4">
                            <span v-if="queryResult.success" class="text-emerald-400 font-semibold">
                                ✓ Query OK (@{{ queryResult.execution_time_ms }} ms) — @{{ queryResult.is_select ? queryResult.rows_count + ' rows' : queryResult.affected_rows + ' rows affected' }}
                            </span>
                            <span v-else class="text-rose-400 font-semibold">
                                ✗ Query Failed (@{{ queryResult.execution_time_ms }} ms)
                            </span>
                        </div>
                    </div>

                    <!-- Results Output -->
                    <div class="flex-1 overflow-auto custom-scrollbar">
                        <div v-if="!queryResult" class="text-center py-20 text-slate-500 text-xs">
                            Enter an SQL query above and click <strong class="text-slate-400">Execute</strong> or press <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px] border border-slate-700">Ctrl + Enter</kbd>.
                        </div>
                        <div v-else-if="!queryResult.success" class="p-4 text-xs font-mono text-rose-300 bg-rose-950/40 whitespace-pre-wrap border border-rose-900/60 rounded m-2">
                            @{{ queryResult.error }}
                        </div>
                        <table v-else-if="queryResult.is_select" class="w-full text-left text-xs font-mono border-collapse">
                            <thead class="bg-slate-900 sticky top-0 border-b border-slate-800 text-slate-400 shadow-sm">
                                <tr>
                                    <th v-for="col in queryResult.columns" :key="col" class="p-2.5 font-semibold text-slate-200">
                                        @{{ col }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                <tr v-for="(row, idx) in queryResult.rows" :key="idx" class="hover:bg-slate-900/60">
                                    <td v-for="col in queryResult.columns" :key="col" class="p-2.5 text-slate-300 max-w-xs truncate border-r border-slate-900/80">
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

            <!-- TAB 3: VISUAL ER DIAGRAM -->
            <div v-if="activeMainTab === 'erd'" class="flex-1 flex flex-col overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-slate-100 flex items-center space-x-2">
                            <i data-lucide="network" class="w-5 h-5 text-teal-400"></i>
                            <span>Visual Database Schema & Relationships (ER Diagram)</span>
                        </h3>
                        <p class="text-xs text-slate-400">Interactive visual map of tables, columns, primary keys, and relationships in <span class="text-teal-300 font-mono">@{{ activeConnection }}</span>.</p>
                    </div>
                </div>

                <div class="flex-1 overflow-auto custom-scrollbar p-6 bg-slate-900/40 border border-slate-800 rounded-2xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div v-for="tbl in tables" :key="tbl.name" class="rounded-xl border border-slate-800 bg-slate-900/90 shadow-xl overflow-hidden hover:border-teal-700/80 transition flex flex-col">
                            <!-- Table Title Header -->
                            <div class="p-3 bg-gradient-to-r from-slate-900 to-slate-800/90 border-b border-slate-800 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i data-lucide="table" class="w-4 h-4 text-teal-400"></i>
                                    <span class="font-mono text-xs font-bold text-slate-100">@{{ tbl.name }}</span>
                                </div>
                                <button @click="setMainTab('tables'); selectTable(tbl.name);" class="text-[10px] px-2 py-0.5 rounded bg-teal-950 border border-teal-800 text-teal-300 hover:bg-teal-900 transition">Browse</button>
                            </div>
                            <!-- Rows summary -->
                            <div class="p-3 text-xs text-slate-400 space-y-1 font-mono">
                                <div class="flex justify-between">
                                    <span>Engine:</span>
                                    <span class="text-slate-300">@{{ tbl.engine }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Rows:</span>
                                    <span class="text-teal-300 font-semibold">@{{ formatNumber(tbl.rows_count) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Data Size:</span>
                                    <span class="text-emerald-400">@{{ tbl.data_size || tbl.size }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: GLOBAL DATABASE SEARCH -->
            <div v-if="activeMainTab === 'search'" class="flex-1 flex flex-col overflow-hidden p-6 space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-100 flex items-center space-x-2">
                        <i data-lucide="search" class="w-5 h-5 text-teal-400"></i>
                        <span>Global Database Search</span>
                    </h3>
                    <p class="text-xs text-slate-400">Search for any text, number, email, or keyword across ALL tables in the active database at once.</p>
                </div>

                <div class="flex items-center space-x-3 shrink-0">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                        <input v-model="globalSearchKeyword" @keyup.enter="runGlobalSearch" type="text" placeholder="Type a keyword to search everywhere (e.g. email, UUID, transaction ID)..." class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-100 font-mono focus:outline-none focus:border-teal-500 shadow-inner">
                    </div>
                    <button @click="runGlobalSearch" :disabled="searchingGlobal" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow flex items-center space-x-1.5">
                        <span v-if="searchingGlobal" class="animate-spin">⟳</span>
                        <span>Search Database</span>
                    </button>
                </div>

                <!-- Global Search Results -->
                <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4">
                    <div v-if="!globalSearchResults" class="text-center py-24 text-slate-500 text-xs">
                        Enter a search keyword above to scan across all tables in @{{ activeConnection }}.
                    </div>
                    <div v-else-if="globalSearchResults.results.length === 0" class="text-center py-16 text-slate-400 text-xs">
                        No matches found across any tables for "<strong class="text-teal-400">@{{ globalSearchResults.keyword }}</strong>".
                    </div>
                    <div v-else class="space-y-4">
                        <div class="text-xs text-slate-400 font-mono">
                            Found <strong class="text-emerald-400">@{{ globalSearchResults.total_matches }}</strong> matches in <strong class="text-teal-400">@{{ globalSearchResults.tables_matched_count }}</strong> table(s):
                        </div>

                        <div v-for="res in globalSearchResults.results" :key="res.table" class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow">
                            <div class="p-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i data-lucide="table" class="w-4 h-4 text-teal-400"></i>
                                    <span class="font-mono text-xs font-bold text-slate-200">@{{ res.table }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-teal-950 border border-teal-800 text-teal-300 font-semibold">@{{ res.matches_count }} matches</span>
                                </div>
                                <button @click="setMainTab('tables'); selectTable(res.table);" class="text-xs text-teal-400 hover:underline">Open Table</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs font-mono border-collapse">
                                    <thead class="bg-slate-950 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th v-for="col in res.columns" :key="col" class="p-2.5 text-slate-400">@{{ col }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 bg-slate-950">
                                        <tr v-for="(row, idx) in res.rows" :key="idx" class="hover:bg-slate-900/50">
                                            <td v-for="col in res.columns" :key="col" class="p-2.5 text-slate-300 max-w-xs truncate border-r border-slate-900/80">
                                                @{{ row[col] !== null ? formatCellValue(row[col]) : 'NULL' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: SCHEMA DIFF & DATABASE COMPARISON -->
            <div v-if="activeMainTab === 'diff'" class="flex-1 flex flex-col overflow-hidden p-6 space-y-4">
                <div>
                    <h3 class="text-base font-bold text-slate-100 flex items-center space-x-2">
                        <i data-lucide="git-compare" class="w-5 h-5 text-teal-400"></i>
                        <span>Schema Diff & Database Comparison</span>
                    </h3>
                    <p class="text-xs text-slate-400">Compare table schemas and column definitions between two connected databases.</p>
                </div>

                <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 flex items-center space-x-4 shrink-0">
                    <div class="flex-1">
                        <label class="block text-slate-400 mb-1 text-xs">Source Connection</label>
                        <select v-model="diffSource" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 font-mono">
                            <option v-for="c in connections" :key="c.name" :value="c.name">@{{ c.name }} (@{{ c.driver }})</option>
                        </select>
                    </div>
                    <div class="text-slate-500 font-bold text-sm pt-4">⇄</div>
                    <div class="flex-1">
                        <label class="block text-slate-400 mb-1 text-xs">Target Connection</label>
                        <select v-model="diffTarget" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 font-mono">
                            <option v-for="c in connections" :key="c.name" :value="c.name">@{{ c.name }} (@{{ c.driver }})</option>
                        </select>
                    </div>
                    <div class="pt-4">
                        <button @click="runSchemaDiff" :disabled="runningDiff" class="px-5 py-2 rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow flex items-center space-x-1.5">
                            <span v-if="runningDiff" class="animate-spin">⟳</span>
                            <span>Compare Schemas</span>
                        </button>
                    </div>
                </div>

                <!-- Diff Results -->
                <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4">
                    <div v-if="!diffResults" class="text-center py-24 text-slate-500 text-xs">
                        Select two connections above and click <strong>Compare Schemas</strong> to inspect differences.
                    </div>
                    <div v-else-if="!diffResults.has_differences" class="p-8 text-center bg-emerald-950/40 border border-emerald-800 rounded-xl text-emerald-300 text-xs">
                        ✓ Both databases are identical in structure! No schema differences found.
                    </div>
                    <div v-else class="space-y-4">
                        <!-- Missing Tables in Target -->
                        <div v-if="diffResults.missing_tables_in_target.length > 0" class="p-4 rounded-xl bg-rose-950/30 border border-rose-800 space-y-2">
                            <h4 class="text-xs font-bold text-rose-300">Tables missing in @{{ diffResults.target_connection }}:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="tbl in diffResults.missing_tables_in_target" :key="tbl" class="px-2.5 py-1 rounded bg-rose-900/60 text-rose-200 font-mono text-xs border border-rose-700">@{{ tbl }}</span>
                            </div>
                        </div>

                        <!-- Missing Tables in Source -->
                        <div v-if="diffResults.missing_tables_in_source.length > 0" class="p-4 rounded-xl bg-cyan-950/30 border border-cyan-800 space-y-2">
                            <h4 class="text-xs font-bold text-cyan-300">Tables present only in @{{ diffResults.target_connection }}:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="tbl in diffResults.missing_tables_in_source" :key="tbl" class="px-2.5 py-1 rounded bg-cyan-900/60 text-cyan-200 font-mono text-xs border border-cyan-700">@{{ tbl }}</span>
                            </div>
                        </div>

                        <!-- Column differences in common tables -->
                        <div v-for="td in diffResults.table_differences" :key="td.table" class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                            <h4 class="text-xs font-bold text-teal-300 font-mono">Table: @{{ td.table }}</h4>
                            <div v-if="td.missing_columns_in_target.length > 0" class="text-xs text-rose-400">
                                Missing columns in target: <strong>@{{ td.missing_columns_in_target.join(', ') }}</strong>
                            </div>
                            <div v-if="td.type_mismatches.length > 0" class="space-y-1">
                                <div v-for="tm in td.type_mismatches" :key="tm.column" class="text-xs font-mono text-slate-300 bg-slate-950 p-2 rounded border border-slate-800 flex justify-between">
                                    <span class="text-amber-400 font-bold">@{{ tm.column }}</span>
                                    <span class="text-slate-400">Source: <span class="text-emerald-400">@{{ tm.source }}</span> | Target: <span class="text-rose-400">@{{ tm.target }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: DATABASE INFO & METRICS -->
            <div v-if="activeMainTab === 'info'" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-100">Database Server Information</h3>
                    <p class="text-xs text-slate-400">Metadata and connection configuration for active database.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="(val, key) in systemInfo" :key="key" class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 space-y-1.5 shadow-sm">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-slate-500 font-semibold">@{{ key }}</span>
                        <div class="text-sm font-bold font-mono text-slate-200 truncate">@{{ val || '-' }}</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-bold text-slate-200">Tables Overview (@{{ tables.length }})</h4>
                    <div class="rounded-xl border border-slate-800 overflow-hidden shadow-sm">
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
                                    <td class="p-3 font-bold text-teal-300 cursor-pointer" @click="setMainTab('tables'); selectTable(t.name);">@{{ t.name }}</td>
                                    <td class="p-3 text-slate-400">@{{ t.engine }}</td>
                                    <td class="p-3 text-slate-200 font-semibold">@{{ formatNumber(t.rows_count) }}</td>
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

    <!-- FLOATING TOAST NOTIFICATION -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="alertMessage" class="fixed bottom-5 right-5 z-50 max-w-sm w-full rounded-xl shadow-2xl p-4 border flex items-center space-x-3 backdrop-blur-md" :class="alertType === 'error' ? 'bg-rose-950/90 border-rose-800 text-rose-200' : 'bg-slate-900/95 border-teal-700/80 text-teal-200'">
            <i :data-lucide="alertType === 'error' ? 'alert-circle' : 'check-circle-2'" class="w-5 h-5 shrink-0" :class="alertType === 'error' ? 'text-rose-400' : 'text-teal-400'"></i>
            <div class="flex-1 text-xs font-medium font-sans">@{{ alertMessage }}</div>
            <button @click="alertMessage = ''" class="text-slate-400 hover:text-white">&times;</button>
        </div>
    </transition>

    <!-- MODAL 1: ADD DYNAMIC DATABASE CONNECTION -->
    <div v-if="showAddConnectionModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-teal-600/20 text-teal-400 flex items-center justify-center">
                        <i data-lucide="plug" class="w-4 h-4"></i>
                    </div>
                    <h3 class="font-bold text-sm text-slate-100">Connect to External Database</h3>
                </div>
                <button @click="showAddConnectionModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1 font-medium">Connection Alias *</label>
                    <input v-model="newConn.name" type="text" placeholder="e.g. analytics_db, production_replica" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1 font-medium">Driver *</label>
                        <select v-model="newConn.driver" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                            <option value="mysql">MySQL / MariaDB</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                            <option value="sqlsrv">SQL Server</option>
                        </select>
                    </div>
                    <div v-if="newConn.driver !== 'sqlite'">
                        <label class="block text-slate-400 mb-1 font-medium">Host & Port</label>
                        <div class="flex space-x-2">
                            <input v-model="newConn.host" type="text" placeholder="127.0.0.1" class="w-2/3 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                            <input v-model="newConn.port" type="text" :placeholder="newConn.driver === 'pgsql' ? '5432' : '3306'" class="w-1/3 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1 font-medium">@{{ newConn.driver === 'sqlite' ? 'Database File Path *' : 'Database Name *' }}</label>
                    <input v-model="newConn.database" type="text" :placeholder="newConn.driver === 'sqlite' ? '/path/to/database.sqlite' : 'my_database'" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-teal-500 font-mono">
                </div>

                <div v-if="newConn.driver !== 'sqlite'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1 font-medium">Username</label>
                        <input v-model="newConn.username" type="text" placeholder="root" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1 font-medium">Password</label>
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
                    <button @click="saveDynamicConnection" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold shadow-md">Connect</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: LARAVEL CODE GENERATOR (Migration / Model / Factory) -->
    <div v-if="showCodeGeneratorModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-3xl w-full p-6 space-y-4 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 shrink-0">
                <div class="flex items-center space-x-2">
                    <i data-lucide="code-2" class="w-4 h-4 text-rose-400"></i>
                    <h3 class="font-bold text-sm text-slate-100">Export @{{ selectedTableName }} as Laravel Code</h3>
                </div>
                <button @click="showCodeGeneratorModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <!-- Generator Tabs -->
            <div class="flex items-center bg-slate-800/80 border border-slate-700/80 rounded-lg p-0.5 text-xs shrink-0">
                <button @click="activeGenTab = 'migration'" :class="activeGenTab === 'migration' ? 'bg-rose-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1.5 rounded transition">
                    Laravel Migration
                </button>
                <button @click="activeGenTab = 'model'" :class="activeGenTab === 'model' ? 'bg-rose-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1.5 rounded transition">
                    Eloquent Model
                </button>
                <button @click="activeGenTab = 'factory'" :class="activeGenTab === 'factory' ? 'bg-rose-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-slate-200'" class="px-3 py-1.5 rounded transition">
                    Model Factory
                </button>
            </div>

            <!-- Code Output Display -->
            <div class="flex-1 overflow-auto custom-scrollbar rounded-xl bg-slate-950 border border-slate-800 p-4 relative">
                <button @click="copyToClipboard(generatedCodes[activeGenTab] || '')" class="absolute top-3 right-3 px-2.5 py-1 text-xs rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center space-x-1 shadow">
                    <i data-lucide="copy" class="w-3 h-3"></i>
                    <span>Copy</span>
                </button>
                <pre class="font-mono text-xs text-rose-300 whitespace-pre-wrap">@{{ generatedCodes[activeGenTab] || 'Generating code...' }}</pre>
            </div>
        </div>
    </div>

    <!-- MODAL 3: MOCK DATA GENERATOR -->
    <div v-if="showMockDataModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <i data-lucide="sparkles" class="w-4 h-4 text-purple-400"></i>
                    <h3 class="font-bold text-sm text-slate-100">Generate Mock Data for @{{ selectedTableName }}</h3>
                </div>
                <button @click="showMockDataModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <p class="text-slate-400">Instantly generate and insert realistic test records with fake names, emails, addresses, numbers, and dates matching column types.</p>
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Number of Records</label>
                    <select v-model="mockCount" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                        <option :value="5">5 Records</option>
                        <option :value="10">10 Records</option>
                        <option :value="25">25 Records</option>
                        <option :value="50">50 Records</option>
                        <option :value="100">100 Records</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4">
                <button @click="showMockDataModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitMockData" :disabled="generatingMock" class="px-4 py-1.5 text-xs rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-semibold shadow flex items-center space-x-1.5">
                    <span v-if="generatingMock" class="animate-spin">⟳</span>
                    <span>Generate & Insert</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: SAVED QUERIES (BOOKMARKS) -->
    <div v-if="showSavedQueriesModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-2xl flex flex-col max-h-[85vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <i data-lucide="bookmark" class="w-4 h-4 text-amber-400"></i>
                    <h3 class="font-bold text-sm text-slate-100">Saved SQL Queries (Bookmarks)</h3>
                </div>
                <button @click="showSavedQueriesModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar space-y-2.5 pr-1 text-xs">
                <div v-if="savedQueries.length === 0" class="text-center py-12 text-slate-500">
                    No saved queries yet. Click <strong>Save Query</strong> in the SQL Console to bookmark queries.
                </div>
                <div v-for="sq in savedQueries" :key="sq.id" class="p-3 rounded-xl border border-slate-800 bg-slate-800/40 hover:border-amber-700/80 transition space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-100">@{{ sq.title }}</span>
                        <div class="flex items-center space-x-2">
                            <button @click="loadSavedQuery(sq)" class="px-2.5 py-1 rounded bg-teal-900/60 hover:bg-teal-800 border border-teal-700/60 text-teal-300 font-semibold text-[11px]">Load Query</button>
                            <button @click="deleteSavedQuery(sq.id)" class="p-1 hover:bg-slate-800 rounded text-slate-500 hover:text-rose-400" title="Delete Bookmark">
                                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                    <pre class="p-2.5 rounded-lg bg-slate-950 text-amber-300 font-mono text-[11px] overflow-x-auto whitespace-pre-wrap">@{{ sq.sql }}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: INSERT / EDIT ROW -->
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
                <button @click="saveRowData" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold shadow">Save Record</button>
            </div>
        </div>
    </div>

    <!-- MODAL 6: CREATE TABLE -->
    <div v-if="showCreateTableModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-3xl w-full p-6 space-y-4 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 shrink-0">
                <h3 class="font-bold text-sm text-slate-100">Create New Table</h3>
                <button @click="showCreateTableModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-4 shrink-0 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1 font-medium">Table Name *</label>
                    <input v-model="newTable.name" type="text" placeholder="e.g. orders, user_profiles" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar space-y-2 pr-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300">Columns</span>
                    <button @click="addNewTableColumn" class="px-2.5 py-1 text-[11px] rounded bg-slate-800 hover:bg-slate-700 text-teal-400 border border-slate-700 flex items-center space-x-1">
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
                <button @click="submitCreateTable" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold">Create Table</button>
            </div>
        </div>
    </div>

    <!-- MODAL 7: IMPORT SQL -->
    <div v-if="showImportModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-sm text-slate-100">Import SQL Script</h3>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1 font-medium">Upload SQL Dump File</label>
                    <input type="file" ref="importFileInput" accept=".sql" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-slate-300 font-mono text-xs">
                </div>
                <div class="text-center text-slate-600 font-bold uppercase text-[10px]">— OR PASTE SQL —</div>
                <div>
                    <textarea v-model="importSqlText" rows="6" placeholder="Paste SQL commands here..." class="w-full bg-slate-800 border border-slate-700 rounded-lg p-3 text-slate-200 font-mono focus:outline-none focus:border-teal-500"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4">
                <button @click="showImportModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitImportSql" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold">Execute Import</button>
            </div>
        </div>
    </div>

    <!-- MODAL 8: ADD COLUMN -->
    <div v-if="showAddColumnModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-sm text-slate-100">Add Column to @{{ selectedTableName }}</h3>
                <button @click="showAddColumnModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1 font-medium">Column Name *</label>
                    <input v-model="newColumn.name" type="text" placeholder="e.g. status, phone_number" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1 font-medium">Data Type *</label>
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
                        <label class="block text-slate-400 mb-1 font-medium">Length / Values</label>
                        <input v-model="newColumn.length" type="text" placeholder="255" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1 font-medium">Default Value</label>
                    <input v-model="newColumn.default" type="text" placeholder="NULL or default value" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-slate-200 font-mono">
                </div>
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" v-model="newColumn.nullable" id="colNullable" class="rounded bg-slate-800 border-slate-700 text-teal-600">
                    <label for="colNullable" class="text-slate-300">Allow NULL</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 border-t border-slate-800 pt-4">
                <button @click="showAddColumnModal = false" class="px-3 py-1.5 text-xs rounded-lg text-slate-400 hover:text-white">Cancel</button>
                <button @click="submitAddColumn" class="px-4 py-1.5 text-xs rounded-lg bg-teal-600 hover:bg-teal-500 text-white font-semibold">Add Column</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const { createApp, ref, computed, onMounted, nextTick, watch } = Vue;

    createApp({
        setup() {
            const config = ref(@json($config));
            const activeConnection = ref(@json($activeConnection));
            const selectedConnection = ref(@json($activeConnection));
            const connections = ref(@json($connections));
            const systemInfo = ref(@json($systemInfo));
            const tables = ref(@json($tables));

            const activeMainTab = ref('tables'); // 'tables', 'query', 'erd', 'search', 'diff', 'info'
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

            // Spreadsheet Inline Editing State
            const inlineEditing = ref({ rowIndex: null, colName: null, value: '' });

            // Table Structure State
            const tableIndexes = ref([]);
            const tableForeignKeys = ref([]);
            const tableCreateSql = ref('');

            // SQL Console State
            const sqlQuery = ref('SELECT * FROM ' + (selectedTableName.value ? '`' + selectedTableName.value + '`' : 'users') + ' LIMIT 50;');
            const queryResult = ref(null);
            let codeEditorInstance = null;

            // Saved Queries
            const savedQueries = ref([]);
            const showSavedQueriesModal = ref(false);

            // Laravel Code Generator
            const showCodeGeneratorModal = ref(false);
            const activeGenTab = ref('migration'); // 'migration', 'model', 'factory'
            const generatedCodes = ref({});

            // Mock Data
            const showMockDataModal = ref(false);
            const mockCount = ref(10);
            const generatingMock = ref(false);

            // Global Search
            const globalSearchKeyword = ref('');
            const globalSearchResults = ref(null);
            const searchingGlobal = ref(false);

            // Schema Diff
            const diffSource = ref(connections.value[0] ? connections.value[0].name : '');
            const diffTarget = ref(connections.value[1] ? connections.value[1].name : (connections.value[0] ? connections.value[0].name : ''));
            const diffResults = ref(null);
            const runningDiff = ref(false);

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
                triggerLucide();
                setTimeout(() => {
                    if (alertMessage.value === msg) alertMessage.value = '';
                }, 5000);
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

            const setMainTab = (tab) => {
                activeMainTab.value = tab;
                triggerLucide();
                if (tab === 'query') {
                    nextTick(() => {
                        initCodeMirror();
                        if (codeEditorInstance) codeEditorInstance.refresh();
                    });
                }
            };

            const setTableTab = (tab) => {
                activeTableTab.value = tab;
                triggerLucide();
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
                    showAlert('Connection test successful!', 'success');
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
                sqlQuery.value = `SELECT * FROM \`${tbl}\` LIMIT 50;`;
                if (codeEditorInstance) codeEditorInstance.setValue(sqlQuery.value);
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

            // Spreadsheet Inline Editing
            const startInlineEdit = (rIdx, colName, currentVal) => {
                inlineEditing.value = {
                    rowIndex: rIdx,
                    colName: colName,
                    value: currentVal !== null ? String(currentVal) : ''
                };
                nextTick(() => {
                    const inputs = document.querySelectorAll('input[ref="inlineInput"]');
                    if (inputs.length > 0) inputs[0].focus();
                });
            };

            const cancelInlineEdit = () => {
                inlineEditing.value = { rowIndex: null, colName: null, value: '' };
            };

            const saveInlineEdit = async (row) => {
                const colName = inlineEditing.value.colName;
                const newVal = inlineEditing.value.value;
                const where = getRowWhere(row);

                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/rows`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ where, data: { [colName]: newVal === '' ? null : newVal } })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    row[colName] = newVal === '' ? null : newVal;
                    cancelInlineEdit();
                    showAlert(`Updated [${colName}] successfully.`);
                } catch (e) {
                    showAlert(e.message, 'error');
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
                triggerLucide();
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
                triggerLucide();
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

            // SQL Console Execution & Saved Queries
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

            const fetchSavedQueries = async () => {
                try {
                    const res = await fetch(`/${config.value.path}/api/saved-queries`);
                    const data = await res.json();
                    savedQueries.value = data.saved_queries || [];
                } catch (e) {}
            };

            const promptSaveQuery = async () => {
                const sql = codeEditorInstance ? codeEditorInstance.getValue() : sqlQuery.value;
                if (!sql.trim()) {
                    showAlert('Query editor is empty.', 'error');
                    return;
                }
                const title = prompt('Enter a name for this saved query bookmark:');
                if (!title) return;

                try {
                    const res = await fetch(`/${config.value.path}/api/saved-queries`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ title, sql })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    savedQueries.value = data.saved_queries;
                    showAlert('Query bookmarked successfully!');
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            const loadSavedQuery = (sq) => {
                sqlQuery.value = sq.sql;
                if (codeEditorInstance) codeEditorInstance.setValue(sq.sql);
                showSavedQueriesModal.value = false;
                showAlert(`Loaded query: ${sq.title}`);
            };

            const deleteSavedQuery = async (id) => {
                try {
                    const res = await fetch(`/${config.value.path}/api/saved-queries/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await res.json();
                    savedQueries.value = data.saved_queries;
                    showAlert('Bookmark removed.');
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // Laravel Code Generator
            const openCodeGeneratorModal = async () => {
                showCodeGeneratorModal.value = true;
                generatedCodes.value = {};
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/generate?type=all`);
                    const data = await res.json();
                    generatedCodes.value = data.code || {};
                } catch (e) {
                    showAlert(e.message, 'error');
                }
            };

            // Mock Data Generator
            const submitMockData = async () => {
                generatingMock.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/tables/${selectedTableName.value}/mock-data`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ count: mockCount.value })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);

                    showMockDataModal.value = false;
                    showAlert(data.message);
                    await fetchTableRows(1);
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    generatingMock.value = false;
                }
            };

            // Global Search
            const runGlobalSearch = async () => {
                if (!globalSearchKeyword.value.trim()) return;
                searchingGlobal.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/search`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ keyword: globalSearchKeyword.value })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    globalSearchResults.value = data;
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    searchingGlobal.value = false;
                }
            };

            // Schema Diff
            const runSchemaDiff = async () => {
                runningDiff.value = true;
                try {
                    const res = await fetch(`/${config.value.path}/api/diff`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ source: diffSource.value, target: diffTarget.value })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error);
                    diffResults.value = data;
                } catch (e) {
                    showAlert(e.message, 'error');
                } finally {
                    runningDiff.value = false;
                }
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
                    showAlert('Data refreshed.');
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
                fetchSavedQueries();
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
                inlineEditing,
                tableIndexes,
                tableForeignKeys,
                tableCreateSql,
                sqlQuery,
                queryResult,
                savedQueries,
                showSavedQueriesModal,
                showCodeGeneratorModal,
                activeGenTab,
                generatedCodes,
                showMockDataModal,
                mockCount,
                generatingMock,
                globalSearchKeyword,
                globalSearchResults,
                searchingGlobal,
                diffSource,
                diffTarget,
                diffResults,
                runningDiff,
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
                setMainTab,
                setTableTab,
                switchConnection,
                testDynamicConnection,
                saveDynamicConnection,
                selectTable,
                fetchTableRows,
                sortBy,
                toggleSelectAllRows,
                startInlineEdit,
                cancelInlineEdit,
                saveInlineEdit,
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
                promptSaveQuery,
                loadSavedQuery,
                deleteSavedQuery,
                openCodeGeneratorModal,
                submitMockData,
                runGlobalSearch,
                runSchemaDiff,
                submitImportSql,
                copyToClipboard,
                refreshAll,
            };
        }
    }).mount('#laramyadmin-app');
</script>
@endpush
